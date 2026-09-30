# Encoder Parameter Presets — Design

Date: 2026-05-01
Issue: opened during implementation plan

## Problem

When a Monitor's `VideoWriter` is set to `ENCODE` (re-encode) instead of `PASSTHROUGH`, the user provides ffmpeg AVOptions through a free-form `EncoderParameters` textarea on the monitor edit page. Each encoder (`libx264`, `libx265`, `h264_nvenc`, `hevc_nvenc`, `h264_vaapi`, `hevc_vaapi`, `libvpx-vp9`, `libsvtav1`, `av1_*`, …) accepts a different set of options with different sensible defaults for CCTV recording. Users have no guidance about which knobs matter, what the recommended values are, or which options the selected encoder will silently ignore.

## Goal

Add a curated, per-encoder preset library to the monitor edit page. Selecting a preset merges its recommended key=value pairs into the existing `EncoderParameters` textarea — overwriting on conflict, preserving user-only keys. A best-effort lint flags option keys that aren't recognised for the currently-selected encoder.

No DB schema change. No C++ change. No new REST endpoints.

## Non-goals

- User-defined / custom presets stored in DB.
- Runtime introspection of `ffmpeg -h encoder=…` to compute the recognised-options list.
- Replacing existing `EncoderParameters` content automatically on upgrade.
- Detecting that an existing textarea "matches" a known preset and pre-selecting it on page load.

## Data model

A new file `web/includes/EncoderTemplates.php` exports a `ZM\EncoderTemplates` class with a static `all()` method returning a dict keyed by encoder name. Each entry has:

- `valid_keys`: array of AVOption keys recognised by that encoder. Hand-curated from `ffmpeg -h encoder=<name>` output, restricted to practically-useful options.
- `presets`: ordered array of preset entries. Each preset has:
  - `id`: globally-unique short slug (`libx264_archival`)
  - `name`: human-friendly label shown in the dropdown (`Archival (high quality)`)
  - `kind`: cross-encoder tag from a fixed vocabulary — `balanced`, `archival`, `low_power`, `low_cpu`, `low_latency`. Drives the cross-encoder kind-match dialog.
  - `params`: associative array of `key=>value` AVOptions.

Initial encoder coverage: `libx264`, `libx265`, `h264_nvenc`, `hevc_nvenc`, `h264_vaapi`, `hevc_vaapi`. Each ships with `Balanced` plus at least one alternative (`Archival` or `Low Power` depending on encoder class). All include an explicit `pix_fmt` to avoid the `yuvj420p`-rejection issue observed with hardware HEVC decoders.

Other encoders in the existing dropdown (`libvpx-vp9`, `libsvtav1`, `av1_*`, …) have no entry in v1; the Preset row is hidden when the current encoder has no entry.

### Example entry

```php
'libx264' => [
  'valid_keys' => [
    'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
    'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
    'bf', 'refs', 'pix_fmt', 'x264-params', 'x264opts',
  ],
  'presets' => [
    ['id'=>'libx264_balanced',  'name'=>'Balanced',                'kind'=>'balanced',
     'params'=>['preset'=>'fast',      'crf'=>'23', 'g'=>'30', 'profile'=>'high',     'pix_fmt'=>'yuv420p']],
    ['id'=>'libx264_archival',  'name'=>'Archival (high quality)', 'kind'=>'archival',
     'params'=>['preset'=>'slow',      'crf'=>'20', 'g'=>'30', 'profile'=>'high',     'pix_fmt'=>'yuv420p']],
    ['id'=>'libx264_lowcpu',    'name'=>'Low CPU',                 'kind'=>'low_cpu',
     'params'=>['preset'=>'ultrafast', 'crf'=>'26', 'g'=>'30', 'profile'=>'baseline', 'pix_fmt'=>'yuv420p']],
  ],
],
```

## UI

Form changes in `web/skins/classic/views/monitor.php`:

1. After the existing `Encoder` `<select>` (currently around line 1196), insert a new `<li class="EncoderPreset">` row containing:
   - `<select id="EncoderPreset" name="newMonitor[__preset_picker]">` populated by JS for the current encoder. The `__preset_picker` name is intentionally not a real DB column — `Monitor::save()` will discard it.
   - `<button type="button" id="ApplyEncoderPreset">Apply preset</button>` — `type=button` to avoid form submit.
2. Move the existing `<li class="EncoderParameters">` (textarea) up so it sits directly under the Preset row.
3. After the textarea, insert `<div id="EncoderParameterDiagnostics" class="encoderParameterDiagnostics"></div>`. The class is new — there is no pre-existing advisory style on this page to reuse.
4. Near the existing JS bootstrap on the page, emit a `<script>` block:
   ```php
   echo '<script>window.ZM_ENCODER_TEMPLATES = '
        . json_encode(ZM\EncoderTemplates::all(), JSON_UNESCAPED_SLASHES)
        . ';</script>';
   ```
5. Load the new JS module via the existing helper: `<script src="<?php echo cache_bust('skins/classic/views/js/monitor-encoder-presets.js') ?>"></script>` placed alongside the page's other view-script `<script>` tags (e.g. next to `MonitorLinkExpression.js` near the bottom of `monitor.php`).

The Preset row is hidden via `display:none` when the current encoder has no entry in `ZM_ENCODER_TEMPLATES`.

The diagnostics div is hidden when empty.

## JS module

New file: `web/skins/classic/views/js/monitor-encoder-presets.js`. IIFE-wrapped, exposes a single `window.ZM_EncoderPresets` namespace.

### Pure functions

```
parseParams(text) -> [{key, value}, ...]
  Splits text on \n or , or # — matching av_dict_parse_string($opts, $str,
  "=", "#,\n", 0) in zm_videostore.cpp:115, which treats all three chars
  as pair separators. Splits each pair on the first "=" (so values may
  contain "="). Trims whitespace from key and value; drops pairs without
  "="; drops pairs with empty key. The returned array preserves the
  user's original ordering for the surviving pairs.

mergeParams(existing, templateParams) -> [{key, value}, ...]
  existing: array from parseParams. templateParams: plain object {k: v}.
  For each key in templateParams: if already in `existing`, overwrite the
  value at that position. Otherwise append at the end. User-only keys are
  preserved untouched. Idempotent under repeated apply of the same preset.

serializeParams(arr) -> text
  Joins entries with \n in "key=value" form, one per line.

lint(parsed, encoder) -> [unrecognisedKey, ...]
  Looks up encoder in ZM_ENCODER_TEMPLATES. Returns the keys present in
  `parsed` that are not in `valid_keys`. Returns [] when the encoder has
  no template entry (no opinion).
```

### Wiring

```
init()
  - Cache references: encoderSelect, presetSelect, applyButton, textarea,
    diagnosticsDiv.
  - Track lastAppliedKind (module-local, initially null).
  - encoderSelect.change -> repopulatePresets() then maybe
    showCrossEncoderDialog().
  - applyButton.click -> applyPreset(presetSelect.value).
  - textarea.input -> runLint().
  - presetSelect.change -> records the kind for "what was last chosen",
    no auto-apply.
  - Initial repopulatePresets() and runLint() on DOMContentLoaded.

repopulatePresets()
  - Empties presetSelect, repopulates from ZM_ENCODER_TEMPLATES[encoder].
  - Hides the Preset row when no entry exists.

applyPreset(presetId)
  - Finds preset in dict, parses textarea, merges, writes back.
  - Updates lastAppliedKind to the preset's kind.
  - Triggers runLint().

showCrossEncoderDialog()
  - Reads lastAppliedKind. If a preset with that kind exists for the new
    encoder, opens bootbox.confirm asking whether to apply the same-kind
    preset. On confirm -> applyPreset(matchingId). On cancel -> no-op.

runLint()
  - parsed = parseParams(textarea.value); enc = encoderSelect.value.
  - unknown = lint(parsed, enc).
  - If empty: clear diagnostics div, hide it.
  - Else: render "Note: a, b are not recognised options for <enc> and
    will be ignored at runtime."
```

## Backend

No changes. The textarea content continues to be parsed by `av_dict_parse_string` in `src/zm_videostore.cpp:115`. The lint never blocks save; ffmpeg silently ignores unknown AVOptions or warns at `avcodec_open2`, matching current behaviour.

## Translations

Add to `web/lang/en_gb.php`:
- `EncoderPreset` -> `'Encoder Preset'`
- `ApplyEncoderPreset` -> `'Apply preset'`
- `EncoderPresetCrossEncoderConfirm` -> `'You had "%s" selected. Apply %s preset?'`
- `EncoderParameterDiagnosticUnrecognised` -> `'%s are not recognised options for %s and will be ignored at runtime.'`

Other locales fall back to en_gb until translated by contributors.

## Testing

The four pure JS functions (`parseParams`, `mergeParams`, `serializeParams`, `lint`) are testable without a DOM. The project doesn't currently maintain a JS test harness; v1 ships with manual verification on pseudo:

1. Apply preset on empty textarea -> textarea matches preset's serialized form.
2. Apply preset twice -> textarea unchanged after the second apply (idempotent).
3. Type a user-only key, apply preset -> user key preserved, template keys overwrite or append.
4. Add a key not in the encoder's `valid_keys` -> diagnostics line appears within a keystroke.
5. Switch encoder dropdown after applying a preset -> dropdown repopulates; if the new encoder has the same kind, confirm dialog appears; on Apply, params merge.
6. Switch encoder dropdown to one with no template entry -> Preset row hides; no dialog.
7. Save monitor with `__preset_picker` posted -> DB `EncoderParameters` reflects only the textarea contents; no `__preset_picker` artefact persisted.

Adding a Jest-style test harness for the pure functions is a reasonable follow-up but is out of scope for v1.

## Risks

- **Stale `valid_keys`.** ffmpeg adds new options across versions; older `valid_keys` will produce false-positive diagnostics. Mitigation: lint is advisory; never blocks save. Maintenance is by PR when users discover gaps.
- **Opinionated preset values.** What's "Balanced" depends on deployment. Mitigation: tuning is a single-file PHP edit, no migration, no per-monitor breakage. Users can always edit the textarea after applying.
- **Cross-encoder dialog noise.** Power users switching encoders intentionally may find the popup annoying. Mitigation: only fires when a same-kind preset exists in the new encoder; cancel leaves the textarea unchanged.

## Out of scope (v1)

- User-defined custom presets in DB.
- ffmpeg-introspection-based `valid_keys`.
- Round-trip preset detection on initial load (pre-selecting the dropdown when the saved textarea matches a known preset).
- Migration to apply a default preset to monitors with empty `EncoderParameters`.

## File-level change list

New:
- `web/includes/EncoderTemplates.php` (~250 lines, mostly data)
- `web/skins/classic/views/js/monitor-encoder-presets.js` (~150 lines)

Modified:
- `web/skins/classic/views/monitor.php` — insert Preset row, move textarea, emit JSON dump, load module
- `web/skins/classic/css/base/views/monitor.css` (and dark/light skin counterparts) — define `.encoderParameterDiagnostics { font-size: 0.9em; color: var(--color-warning, #b58900); }` reusing existing skin variables where defined; otherwise a literal colour fallback per skin
- `web/lang/en_gb.php` — four new translation keys

Unchanged:
- DB schema, all migrations
- `src/zm_videostore.cpp` and other C++ sources
- `web/includes/Monitor.php`
- REST API
