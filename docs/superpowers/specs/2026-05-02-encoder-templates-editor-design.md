# Encoder Templates Editor — Design

Date: 2026-05-02
Issue: opened during implementation plan
Builds on: `docs/superpowers/specs/2026-05-01-encoder-presets-design.md` (v1)

## Problem

The v1 encoder-templates feature shipped a hand-curated PHP array of 14 templates across six encoders. Users have no way to add their own templates, edit shipped ones, or delete templates they don't want — the only path is editing `web/includes/EncoderTemplates.php` and shipping a release. This spec extends v1 with:

- A DB table for templates so they're mutable at runtime.
- A CakePHP REST API for CRUD on templates.
- A new Options page tab "Encoder Templates" with a flat-list editor.
- Renaming v1's mixed Preset/Template terminology to consistently use Template.
- Folding in a "Copy this template" affordance in the editor.

The advisory lint (`valid_keys`) stays in PHP code — it's ffmpeg vocabulary, not user data.

## Decisions made during brainstorming

- **Storage: DB-only** (Q1 option a). The template data moves into a new `EncoderTemplates` table; the v1 hand-curated array is seeded by a one-shot migration. ZM upgrades do not re-seed; users keep their edits and additions, and don't automatically pick up improved defaults from new releases. Tradeoff explicitly accepted.
- **No `Kind` column.** The v1 `Kind` enum (`balanced`/`archival`/`low_power`/...) only served the cross-encoder same-kind dialog. Replacing it with a case-insensitive Name match plus a `UNIQUE (Encoder, Name)` constraint preserves the matching behaviour and adds a free-form `Description` field for verbose intent.
- **Editor UI: flat list** (Q2 option X). One table with Encoder filter dropdown above; per-row Edit/Copy/Delete actions; an inline edit form below the table. Mirrors `_options_storage.php` / `_options_servers.php`.
- **Menu placement: top-level Options tab "Encoder Templates"** (Q4 option M1).
- **Monitor form data flow: PHP-render** (Q5). The monitor edit page's `window.ZM_ENCODER_TEMPLATES` dump now reads from the DB at page-render time. The page does not fetch from the API on load.
- **Renames:** v1's mixed Preset/Template naming corrected wholesale before the editor lands. Since v1 has not yet been pushed to upstream, this is one of v2's commits rather than a follow-up.
- **valid_keys location:** stays in PHP. Runtime ffmpeg introspection is filed as a deferred follow-up — the hand-curated list is fine for now and opaque pass-through options (`x264-params`/`x265-params`) would defeat introspection anyway.

## Schema

```sql
CREATE TABLE EncoderTemplates (
  Id          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  Encoder     VARCHAR(32)  NOT NULL,
  Name        VARCHAR(64)  NOT NULL,
  Description TEXT,
  Params      TEXT         NOT NULL DEFAULT '',
  PRIMARY KEY (Id),
  INDEX (Encoder),
  UNIQUE KEY (Encoder, Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- `Id` autoincrement primary key. Replaces v1's string slugs (`libx264_balanced`).
- `Encoder` is a plain VARCHAR — no FK; the canonical encoder vocabulary lives in the hardcoded `<select>` in `monitor.php`.
- `Name` 64 chars, unique per encoder. Cross-encoder kind-match is now a case-insensitive `LOWER(Name)` lookup against the new encoder's rows.
- `Description` is optional free-form text shown in the editor's edit form and as `<option title="...">` tooltip on the monitor edit page's preset dropdown.
- `Params` is the same `key=value\n…` text the monitor form already uses, round-tripping through `parseParams` / `serializeParams`.
- The migration both creates the table (in `db/zm_create.sql.in`) and seeds the 14 v1 defaults (in a new `db/zm_update-1.39.NEXT.sql` — the implementation plan picks the concrete version against `version.txt` at write-time). Each shipped row gets a Description.

### Seed data

The 14 rows from v1's `EncoderTemplates::all()` migrated verbatim. Each gets a Description summarising intent. Examples:

| Encoder | Name | Description |
| --- | --- | --- |
| `libx264` | `Balanced` | "1080p recording with reasonable CPU cost. Good default for most cameras." |
| `libx264` | `Archival (high quality)` | "Slow encode for archival storage; substantially smaller files at higher CPU cost." |
| `libx264` | `Low CPU` | "Highest encoding speed for slow CPUs; quality and file size trade off." |
| `hevc_nvenc` | `Balanced` | "1080p HEVC on NVIDIA GPU; sane vbr+cq defaults, no B-frames." |
| `hevc_nvenc` | `Low Power` | "Faster preset for thermally-constrained NVIDIA hardware." |
| ... | ... | ... |

Final wording lives in the migration SQL.

## Backend — CakePHP REST API

Mirrors `ControlsController` in shape and conventions.

**Files (new):**
- `web/api/app/Model/EncoderTemplate.php`
- `web/api/app/Controller/EncoderTemplatesController.php`

**Model validation:**
- `Encoder`: not blank.
- `Name`: not blank, unique per `Encoder` (CakePHP `isUnique` scoped via `findExistsBy`).
- `Description`: optional.
- `Params`: defaults to empty string; no further validation (advisory lint is client-side).

**Controller actions (auto-routed):**
- `index` — `GET /api/encoderTemplates.json`. List all. Supports the existing ZM filter pattern: `GET /api/encoderTemplates/index/Encoder=:libx264.json` for client-side filtering.
- `view` — `GET /api/encoderTemplates/:id.json`.
- `add` — `POST /api/encoderTemplates.json`.
- `edit` — `PUT /api/encoderTemplates/:id.json`.
- `delete` — `DELETE /api/encoderTemplates/:id.json`.

**Auth:**
- Inherits JWT/session check from `AppController`.
- `index`/`view` allowed at viewer level (so the monitor edit page works for non-admin users).
- `add`/`edit`/`delete` gated on `System` privilege, matching how Storage / Servers / Users are gated.

**Errors:**
- 401 on missing auth.
- 403 on insufficient privilege.
- 422 on validation failure with the model's error array under `errors` key (existing CakePHP idiom).
- 404 on missing `:id`.

## PHP helper rewrite — `web/includes/EncoderTemplates.php`

The v1 file shrinks from a 98-line hand-curated array to a thin DB-backed wrapper with the static `valid_keys` table:

```php
namespace ZM;

class EncoderTemplates {
  private const VALID_KEYS = [
    'libx264'    => ['preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
                     'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
                     'bf', 'refs', 'pix_fmt', 'x264-params', 'x264opts'],
    'libx265'    => [/* … */],
    'h264_nvenc' => [/* … */],
    'hevc_nvenc' => [/* … */],
    'h264_vaapi' => [/* … */],
    'hevc_vaapi' => [/* … */],
  ];

  public static function all(): array {
    $rows = dbFetchAll('SELECT * FROM EncoderTemplates ORDER BY Encoder, Name');
    $byEncoder = [];
    foreach (self::VALID_KEYS as $enc => $keys) {
      $byEncoder[$enc] = ['valid_keys' => $keys, 'templates' => []];
    }
    foreach ($rows as $row) {
      $enc = $row['Encoder'];
      if (!isset($byEncoder[$enc])) {
        $byEncoder[$enc] = ['valid_keys' => [], 'templates' => []];
      }
      $byEncoder[$enc]['templates'][] = [
        'id'          => (int)$row['Id'],
        'name'        => $row['Name'],
        'description' => $row['Description'],
        'params'      => self::paramsTextToObject($row['Params']),
      ];
    }
    return $byEncoder;
  }

  public static function validKeysFor(string $encoder): array {
    return self::VALID_KEYS[$encoder] ?? [];
  }

  // Helper: convert "key=value\nkey=value" text into the {key: value} object
  // shape expected by the JS module's mergeParams().
  private static function paramsTextToObject(string $text): array { /* … */ }
}
```

Used by:
- `web/skins/classic/views/js/monitor.js.php` — `window.ZM_ENCODER_TEMPLATES = <?= json_encode(ZM\EncoderTemplates::all()) ?>;` (unchanged from v1; the call returns the same dict shape).
- The new editor's PHP page (rendering encoder filter dropdown options).

## Editor UI — `web/skins/classic/views/_options_encoderTemplates.php`

Pattern mirrors `_options_storage.php` / `_options_servers.php`.

```
┌─ Encoder Templates ─────────────────────────────────────────────┐
│  Encoder filter: [All encoders          ▼]   [+ New Template]   │
│  ┌─────────────┬─────────────────────────┬──────────┬─────────┐ │
│  │ Encoder     │ Name                    │ Params   │ Actions │ │
│  ├─────────────┼─────────────────────────┼──────────┼─────────┤ │
│  │ libx264     │ Balanced                │ preset…  │ ✏ ⎘ ✕  │ │
│  │ libx264     │ Archival (high quality) │ preset…  │ ✏ ⎘ ✕  │ │
│  │ libx264     │ Low CPU                 │ preset…  │ ✏ ⎘ ✕  │ │
│  │ libx265     │ Balanced                │ preset…  │ ✏ ⎘ ✕  │ │
│  │ ...                                                        │ │
│  └─────────────┴─────────────────────────┴──────────┴─────────┘ │
│                                                                 │
│  ── Edit form (hidden until row's Edit/Copy or New is clicked) ─│
│  Encoder:     [libx264             ▼]                           │
│  Name:        [_______________________]                         │
│  Description: [_______________________]                         │
│                                                                 │
│  Params:                                                        │
│  ┌──────────────────────────────────────┐                       │
│  │ preset=fast                          │                       │
│  │ crf=23                               │                       │
│  │ ...                                  │                       │
│  └──────────────────────────────────────┘                       │
│  ⚠ <lint diagnostics>                                           │
│                                                                 │
│  [Save]   [Cancel]                                              │
└─────────────────────────────────────────────────────────────────┘
```

**Filter dropdown:**
- "All encoders" plus one entry per encoder that has at least one template.
- Filtering happens client-side — the table renders all rows and the filter applies a CSS `display:none` per non-matching row.

**Table columns:**
- `Encoder` (readonly, sortable client-side).
- `Name` (readonly).
- `Params` (truncated to 60 chars + tooltip showing full content).
- `Actions`: Edit ✏ / Copy ⎘ / Delete ✕.

`Description` is omitted from the table to keep rows narrow; visible in the edit form.

**Action behaviours:**
- **Edit**: opens the form below the table pre-populated from the row, in "edit mode" (Save → PUT). The Encoder dropdown is **disabled** in edit mode (changing the encoder of an existing template would invite confusing UI; if the user really wants that, they can Copy + Delete).
- **Copy**: opens the form pre-populated with the row's Encoder, Description, and Params. Name is `<source name> + " Copy"` (e.g. `Balanced` → `Balanced Copy`). Form is in "create mode" (Save → POST). If the auto-suffixed name collides with an existing template on the same encoder, the API returns the validation error and the form stays open with the message.
- **Delete**: native `confirm("Delete template '<name>' for <encoder>?")`, then DELETE via the API. Row removed from the table on success.
- **+ New Template**: opens an empty form in create mode. If the filter dropdown is narrowed to a specific encoder, that encoder is preselected.

**Edit form fields:**
- Encoder — `<select>` with the same hardcoded encoder list as `monitor.php`. Disabled in edit mode (only).
- Name — text input, max 64 chars.
- Description — textarea, optional.
- Params — textarea with the same advisory lint we already built for the monitor form. The JS module's `parseParams` and `lint` functions are reused directly — `_options_encoderTemplates.php` includes the same `monitor-encoder-templates.js` script and bootstraps the lint against this textarea + the form's encoder select.
- Save / Cancel buttons. Save POSTs (or PUTs) via the API. On success, the table row is updated/appended and the form hides. On failure, the form stays open with the error rendered above the fields.

**Permission gate:** the entire `_options_encoderTemplates.php` page is wrapped in `if (canView('System')) { ... } else { error }`. Reads happen via PHP-render (since the user can already view system options if they reached this tab); writes happen via the API which has its own gate. Defence in depth.

## Monitor edit page integration

Tiny diff from v1:

- `web/skins/classic/views/js/monitor.js.php` — `window.ZM_ENCODER_TEMPLATES` now comes from `ZM\EncoderTemplates::all()` reading the DB. Call signature unchanged; data shape unchanged.
- `web/skins/classic/views/js/monitor-encoder-templates.js` (renamed from `monitor-encoder-presets.js`) — passes the new `description` field into the dropdown options as `<option title="...">` for hover tooltip. Otherwise unchanged.
- The cross-encoder kind-match logic switches from `kind === oldKind` to a case-insensitive `Name === oldName` check. The `lastAppliedKind` module-local renames to `lastAppliedName`.

## Renames (v1 → v2)

Applied as one renaming commit on the v2 branch, before the editor work begins.

| Old (v1) | New (v2) |
| --- | --- |
| DOM id `EncoderPreset` | `EncoderTemplate` |
| DOM id `EncoderPresetRow` | `EncoderTemplateRow` |
| DOM id `ApplyEncoderPreset` | `ApplyEncoderTemplate` |
| Translation `EncoderPreset` | `EncoderTemplate` |
| Translation `ApplyEncoderPreset` | `ApplyEncoderTemplate` |
| File `web/skins/classic/views/js/monitor-encoder-presets.js` | `monitor-encoder-templates.js` |
| Namespace `window.ZM_EncoderPresets` | `window.ZM_EncoderTemplates` |
| File `tests/js/encoder-presets.test.js` | `tests/js/encoder-templates.test.js` |
| Inner array `presets:` in the dict | `templates:` |
| Inner field `kind:` on each entry | (removed; replaced by name match) |
| Module-local `lastAppliedKind` | `lastAppliedName` |
| CSS class `encoderParameterDiagnostics` | (unchanged — wasn't preset-flavoured) |

## File-level change list

**New:**
- `web/api/app/Model/EncoderTemplate.php`
- `web/api/app/Controller/EncoderTemplatesController.php`
- `web/skins/classic/views/_options_encoderTemplates.php`
- `db/zm_update-1.39.NEXT.sql` (table create + seed)

**Modified:**
- `db/zm_create.sql.in` — `CREATE TABLE EncoderTemplates` block + seed inserts.
- `web/skins/classic/views/options.php` — new `else if ($tab == 'encoderTemplates')` branch including `_options_encoderTemplates.php`.
- `web/skins/classic/views/_options_menu.php` — append "Encoder Templates" link to the Options menu.
- `web/includes/EncoderTemplates.php` — rewrite as DB-backed wrapper; `valid_keys` survives as a private const.
- `web/skins/classic/views/js/monitor.js.php` — call signature unchanged; data flows from DB now.
- `web/skins/classic/views/js/monitor-encoder-templates.js` (renamed) — new tooltip rendering, name-based cross-encoder match, drop `kind`.
- `tests/js/encoder-templates.test.js` (renamed) — update test fixtures to remove `kind`, add tests for case-insensitive name match.
- `web/skins/classic/views/monitor.php` — DOM id renames only.
- `web/lang/en_gb.php` — translation key renames + new keys for the editor (column headers, button labels, validation messages).

**Unchanged:**
- The C++ recording path (`src/zm_videostore.cpp`) — Params text format identical.
- `web/includes/Monitor.php` — no new column.
- `web/skins/classic/css/*/views/monitor.css` — `.encoderParameterDiagnostics` rule reused for the editor's lint area.

## Release plan

v1 and v2 ship together — the squashed v1 commit on local `master` will be combined with v2's commits before pushing to upstream. That removes two classes of risk that would otherwise apply: nobody sees a release that has the v1 PHP-array world, and the rename of v1's DOM ids / JS module / translation keys is invisible to downstream because v1 was never released with the old names.

## Risks

- **Unique key collisions on seed.** The `INSERT` migration assumes the table is empty. If a downstream packager has somehow pre-populated the table, the seed `INSERT`s fail. Mitigation: use `INSERT IGNORE` in the seed migration so a partial pre-populate doesn't break upgrade.
- **API privilege check.** Forgetting the `System`-privilege gate on `add`/`edit`/`delete` would let any logged-in user mutate templates. Tests must cover this — at minimum, an integration test that POSTs from a viewer-level session and expects 403.

## Out of scope (deferred)

- Runtime ffmpeg introspection for `valid_keys`. Hand-curated PHP list is fine for now; opaque pass-through options like `x264-params` would defeat introspection. Filed as a follow-up.
- Per-user templates or template ownership. Templates are global; any System-priv user can edit any template.
- Idempotent re-seed of shipped defaults on upgrade. Per Q1 (a).
- Import / export of templates (e.g. JSON file upload / download).
- Template tags or grouping beyond the encoder grouping that's already implicit.
- Bulk edit / bulk delete in the editor.
