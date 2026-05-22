# Encoder Templates Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the v1 hand-curated encoder-template data into a new `EncoderTemplates` MySQL table, expose CRUD via a CakePHP REST API, add an Options-page editor for list/edit/copy/delete, and rename v1's mixed Preset/Template terminology to consistently use Template throughout.

**Architecture:** The data shifts from a static PHP array (`web/includes/EncoderTemplates.php`'s v1 form) to a DB table seeded from the v1 array via a one-shot migration. The PHP file becomes a thin DB-backed wrapper that still hosts the static `valid_keys` allow-list (ffmpeg vocabulary, not user data). A CakePHP REST controller (`EncoderTemplatesController`) and model (`EncoderTemplate`) provide CRUD over the table. A new Options tab "Encoder Templates" hosts a flat-list editor mirroring `_options_servers.php`. The monitor edit page integration is unchanged in shape — `window.ZM_ENCODER_TEMPLATES` is still server-rendered from PHP — but the data underneath now comes from the DB.

**Tech Stack:** PHP 7.4+, CakePHP 2.x (web/api), MySQL/MariaDB, browser JS (jQuery + bootstrap-table + bootbox), the v1 JS module's pure functions (parseParams/mergeParams/serializeParams/lint) reused, Node ≥ 18 for running the existing pure-function tests.

**Spec:** `docs/superpowers/specs/2026-05-02-encoder-templates-editor-design.md`

**Pre-conditions:**
- Currently on branch `encoder-presets-editor` off local `master`. The v1 squashed commit `d58833d2a` is on `master` but not yet pushed; v1+v2 will be squashed together before push.
- `version.txt` reads `1.39.5`. The new migration will be `db/zm_update-1.39.6.sql` and `version.txt` bumps to `1.39.6` in the same commit as the migration.

---

## Task 1: Open the GitHub issue

**Files:** none yet.

- [ ] **Step 1: Open the issue and capture the number**

```bash
gh issue create \
  --title "Editor + REST API for encoder templates" \
  --label enhancement \
  --body "$(cat <<'EOF'
v2 of the encoder-templates feature. Move the hand-curated data into a new EncoderTemplates DB table, expose CRUD via a CakePHP REST API, add an Options-page editor (list / edit / copy / delete), and rename the v1 mixed Preset/Template terminology to consistently use Template.

Spec: docs/superpowers/specs/2026-05-02-encoder-templates-editor-design.md
Plan: docs/superpowers/plans/2026-05-02-encoder-templates-editor.md
EOF
)"
```

Note the returned issue number; substitute it for `<N>` in subsequent commit messages.

- [ ] **Step 2: Rename the branch to match**

```bash
git branch -m encoder-presets-editor <N>-encoder-templates-editor
```

(Branch local-only; no upstream rename needed.)

---

## Task 2: Mechanical rename — Preset → Template

This is the largest single mechanical commit on the branch. It only changes names, not behaviour.

**Files:**
- Rename: `web/skins/classic/views/js/monitor-encoder-presets.js` → `monitor-encoder-templates.js`
- Rename: `tests/js/encoder-presets.test.js` → `tests/js/encoder-templates.test.js`
- Modify: `web/skins/classic/views/monitor.php` (DOM ids)
- Modify: `web/skins/classic/views/js/monitor.js.php` (no name change here, just the comment)
- Modify: `web/lang/en_gb.php` (translation keys)
- Modify: the new `monitor-encoder-templates.js` (namespace + inner data shape access)
- Modify: the renamed test file (require path + namespace usages)

- [ ] **Step 1: Rename the JS module file**

```bash
git mv web/skins/classic/views/js/monitor-encoder-presets.js \
        web/skins/classic/views/js/monitor-encoder-templates.js
```

- [ ] **Step 2: Rename the test file**

```bash
git mv tests/js/encoder-presets.test.js \
        tests/js/encoder-templates.test.js
```

- [ ] **Step 3: Rewrite the namespace + inner data shape in the JS module**

Open `web/skins/classic/views/js/monitor-encoder-templates.js`. Apply these textual replacements (in order; the order matters because the second only matches what the first leaves):

- Replace every `ZM_EncoderPresets` with `ZM_EncoderTemplates`.
- Replace every `t.presets` with `t.templates`.
- Replace every `t.presets.length` was already covered by the previous step — verify.
- The function that iterates `t.presets[i]` (lines around `findPreset`, `presetWithKind`, `repopulatePresets`) — variable names like `preset` inside the loop body should rename to `template`. Specifically, in `findPreset`/`presetWithKind`/`repopulatePresets`/`applyPreset`/`onEncoderChange`, every local `preset` becomes `template`.
- The `lastAppliedKind` module-local renames to `lastAppliedName` (we drop `kind` in favour of name-based matching in Task 3 — but the variable rename happens here so the rename commit is purely string substitution).

After replacement, no occurrence of `Preset` should remain in the file. Verify with:

```bash
grep -n "Preset\|preset" web/skins/classic/views/js/monitor-encoder-templates.js | grep -v "this\\.encoderSel\\|preset_\\|EncoderPreset\\|key.*preset\\|val.*preset\\|preset=\\|preset:\\|the preset\\|preset's"
```

(Some `preset`-as-noun usages survive in user-facing strings — those are fine and Task 3 also revisits them; for now ensure the variable/identifier-level renames are complete.)

- [ ] **Step 4: Rewrite the test file's require path and namespace**

In `tests/js/encoder-templates.test.js`:

- Change `require(...path... 'monitor-encoder-presets.js')` to `'monitor-encoder-templates.js'`.
- Change every `const ZM = ...` reference to use the renamed module (path was the only thing).
- Change `ZM_EncoderPresets` references — there shouldn't be any in the test (it does `const ZM = require(...)` not `window.ZM_EncoderPresets`), but verify.
- Inner data shape used in the `lint` test fixture: rename the fixture var from `TEMPLATES_FIXTURE` (already correct name) and check the fixture entries don't use `presets` — they use the shape `{libx264: {valid_keys: [...], presets: []}, ...}` which is wrong now. The fixture should use `templates: []` instead of `presets: []`. Update.

- [ ] **Step 5: Run the tests to verify the rename didn't break anything**

```bash
node tests/js/encoder-templates.test.js
```

Expected: 23 passed, 0 failed (same as before the rename).

- [ ] **Step 6: Update DOM ids in monitor.php**

In `web/skins/classic/views/monitor.php`:

- `id="EncoderPreset"` → `id="EncoderTemplate"`
- `id="EncoderPresetRow"` → `id="EncoderTemplateRow"`
- `id="ApplyEncoderPreset"` → `id="ApplyEncoderTemplate"`
- `class="EncoderPreset"` → `class="EncoderTemplate"`
- `name="newMonitor[__preset_picker]"` → `name="newMonitor[__template_picker]"`

- [ ] **Step 7: Update DOM-id references in the JS module**

In `web/skins/classic/views/js/monitor-encoder-templates.js`, replace every `getElementById('EncoderPreset')`, `'EncoderPresetRow'`, `'ApplyEncoderPreset'` with `'EncoderTemplate'`, `'EncoderTemplateRow'`, `'ApplyEncoderTemplate'` respectively.

- [ ] **Step 8: Update the cache_bust path in monitor.php**

```php
<script src="<?php echo cache_bust('skins/classic/views/js/monitor-encoder-templates.js') ?>"></script>
```

(Path changed from `monitor-encoder-presets.js` → `monitor-encoder-templates.js`.)

- [ ] **Step 9: Update translation keys in en_gb.php**

In `web/lang/en_gb.php`:

- `'EncoderPreset'        => 'Encoder Preset'` → `'EncoderTemplate'        => 'Encoder Template'`
- `'ApplyEncoderPreset'   => 'Apply preset'` → `'ApplyEncoderTemplate'   => 'Apply template'`

In `web/skins/classic/views/monitor.php`, update the `translate(...)` calls that reference these keys (`translate('EncoderPreset')` → `translate('EncoderTemplate')`, same for Apply).

- [ ] **Step 10: Verify and commit**

```bash
node tests/js/encoder-templates.test.js
npx eslint web/skins/classic/views/js/monitor-encoder-templates.js
php -l web/skins/classic/views/monitor.php web/lang/en_gb.php
```

All clean.

```bash
git add -A
git commit -m "$(cat <<'EOF'
refactor: rename Preset to Template throughout encoder-templates feature refs #<N>

The v1 feature used "Templates" for the file/class/global but "Preset"
for DOM ids, JS module file, namespace, test file, and the inner data
shape. Make the terminology consistent so v2 (the editor) doesn't
inherit the inconsistency.

No behavioural changes — purely string substitution + file renames.
The 23-test pure-function suite still passes; ESLint clean.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Drop `kind`; switch cross-encoder match to case-insensitive Name

**Files:**
- Modify: `web/skins/classic/views/js/monitor-encoder-templates.js`
- Modify: `tests/js/encoder-templates.test.js`
- Modify: `web/includes/EncoderTemplates.php` (still the static-data v1 file at this point — the next task replaces it)

- [ ] **Step 1: Update the test fixture**

In `tests/js/encoder-templates.test.js`, find the `TEMPLATES_FIXTURE`:

```javascript
const TEMPLATES_FIXTURE = {
  libx264: {
    valid_keys: ['preset', 'crf', 'g', 'profile', 'pix_fmt'],
    templates: [],
  },
  h264_nvenc: {
    valid_keys: ['preset', 'rc', 'cq', 'g', 'profile', 'pix_fmt'],
    templates: [],
  },
};
```

(Already updated in Task 2 to use `templates`.) No content change needed in this step.

- [ ] **Step 2: Add new tests for case-insensitive name match**

Add a new section to `tests/js/encoder-templates.test.js`, immediately before the `console.log('\n' + passed + ' passed, ...');` summary line:

```javascript
console.log('\nfindTemplateByName (cross-encoder match)');
const NAME_FIXTURE = {
  libx264: {
    valid_keys: ['preset'],
    templates: [
      {id: 1, name: 'Balanced',   description: '', params: {preset: 'fast'}},
      {id: 2, name: 'Archival',   description: '', params: {preset: 'slow'}},
    ],
  },
  libx265: {
    valid_keys: ['preset'],
    templates: [
      {id: 3, name: 'Balanced',   description: '', params: {preset: 'fast'}},
      {id: 4, name: 'archival',   description: '', params: {preset: 'slow'}}, // lowercase variant
    ],
  },
};
test('findTemplateByName: exact match', () => {
  const t = ZM.findTemplateByName('libx265', 'Balanced', NAME_FIXTURE);
  assert.strictEqual(t.id, 3);
});
test('findTemplateByName: case-insensitive match', () => {
  const t = ZM.findTemplateByName('libx265', 'Archival', NAME_FIXTURE);
  assert.strictEqual(t.id, 4);
});
test('findTemplateByName: no match returns null', () => {
  const t = ZM.findTemplateByName('libx265', 'Low Power', NAME_FIXTURE);
  assert.strictEqual(t, null);
});
test('findTemplateByName: unknown encoder returns null', () => {
  const t = ZM.findTemplateByName('libsvtav1', 'Balanced', NAME_FIXTURE);
  assert.strictEqual(t, null);
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
node tests/js/encoder-templates.test.js
```

Expected: 4 failures with `ZM.findTemplateByName is not a function`.

- [ ] **Step 4: Replace `presetWithKind` with `findTemplateByName` in the JS module**

In `web/skins/classic/views/js/monitor-encoder-templates.js`, find:

```javascript
  function presetWithKind(encoder, kind) {
    if (!kind) return null;
    const t = getTemplates()[encoder];
    if (!t || !Array.isArray(t.templates)) return null;
    for (let i = 0; i < t.templates.length; i++) {
      if (t.templates[i].kind === kind) return t.templates[i];
    }
    return null;
  }
```

Replace with:

```javascript
  function findTemplateByName(encoder, name, templatesArg) {
    if (!name) return null;
    const all = templatesArg || getTemplates();
    const t = all[encoder];
    if (!t || !Array.isArray(t.templates)) return null;
    const target = String(name).toLowerCase();
    for (let i = 0; i < t.templates.length; i++) {
      if (String(t.templates[i].name).toLowerCase() === target) return t.templates[i];
    }
    return null;
  }
```

- [ ] **Step 5: Update `onEncoderChange` to use name-based match**

Find the `onEncoderChange` body:

```javascript
  function onEncoderChange() {
    const encoderSel = document.getElementById('ZmEncoder');
    if (!encoderSel) return;
    const previousKind = lastAppliedKind;
    repopulatePresets();
    runLint();
    if (!previousKind) return;
    const match = presetWithKind(encoderSel.value, previousKind);
    if (!match) return;
    ...
```

Replace with:

```javascript
  function onEncoderChange() {
    const encoderSel = document.getElementById('ZmEncoder');
    if (!encoderSel) return;
    const previousName = lastAppliedName;
    repopulateTemplates();
    runLint();
    if (!previousName) return;
    const match = findTemplateByName(encoderSel.value, previousName);
    if (!match) return;
    const msg = 'You had a "' + previousName + '" template selected. ' +
                'Apply ' + match.name + ' for ' + encoderSel.value + '?';
    if (window.confirm(msg)) {
      applyTemplate(match.id);
    }
  }
```

- [ ] **Step 6: Rename the helper functions throughout**

Apply these final renames in `monitor-encoder-templates.js` (the rest of the file):

- `repopulatePresets` → `repopulateTemplates` (function name + every call site)
- `applyPreset` → `applyTemplate` (function name + every call site)
- `findPreset(encoder, presetId)` → `findTemplate(encoder, templateId)` (function name; arg name; every call site)
- The `lastAppliedKind` variable from Task 2 should already be `lastAppliedName`. Verify.
- The `presetSel.options[presetSel.selectedIndex]` change-handler that was setting `lastAppliedKind = opt.dataset.kind` now needs to set `lastAppliedName = opt.textContent` (the visible name string).

Update the `presetSel.addEventListener('change', ...)` body to:

```javascript
    presetSel.addEventListener('change', function() {
      const opt = presetSel.options[presetSel.selectedIndex];
      if (opt) lastAppliedName = opt.textContent;
    });
```

- [ ] **Step 7: Update the `repopulateTemplates` body to drop `dataset.kind`**

In `repopulateTemplates`:

```javascript
    for (let i = 0; i < t.templates.length; i++) {
      const tmpl = t.templates[i];
      const opt = document.createElement('option');
      opt.value = tmpl.id;
      opt.textContent = tmpl.name;
      if (tmpl.description) opt.title = tmpl.description;
      presetSel.appendChild(opt);
    }
```

(Drops `opt.dataset.kind = tmpl.kind`; adds the description tooltip.)

- [ ] **Step 8: Expose `findTemplateByName` on the `api` object**

```javascript
  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
    serializeParams: serializeParams,
    lint: lint,
    findTemplateByName: findTemplateByName,
    init: init,
  };
```

- [ ] **Step 9: Drop `kind` from the v1 PHP data file**

In `web/includes/EncoderTemplates.php`, remove the `'kind' => '...'` field from every preset entry. (This file is going to be replaced wholesale in Task 5, but we keep it consistent with the JS data shape in this commit so the in-between state still works.)

Also rename the inner key from `'presets'` to `'templates'` in the static return shape — match the JS module's expectations.

- [ ] **Step 10: Verify and commit**

```bash
node tests/js/encoder-templates.test.js
npx eslint web/skins/classic/views/js/monitor-encoder-templates.js
php -l web/includes/EncoderTemplates.php
```

All clean. 27 tests pass (23 prior + 4 new).

```bash
git add -A
git commit -m "$(cat <<'EOF'
refactor: switch cross-encoder template match from kind to case-insensitive Name refs #<N>

The v1 cross-encoder dialog used a 'kind' enum to find an analogue
template on the new encoder. The v2 schema drops kind in favour of a
unique (Encoder, Name) constraint, so we can match by name with a
case-insensitive comparison instead. New helper findTemplateByName
replaces presetWithKind. Helper functions are also renamed for
consistency: repopulatePresets -> repopulateTemplates, applyPreset
-> applyTemplate, findPreset -> findTemplate, lastAppliedKind ->
lastAppliedName.

The PHP data file is updated in lockstep so the static array still
matches the JS shape; Task 4 replaces it with a DB-backed wrapper.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Schema migration + seed

**Files:**
- Modify: `db/zm_create.sql.in` (CREATE TABLE block + seed inserts)
- Create: `db/zm_update-1.39.6.sql`
- Modify: `version.txt`

- [ ] **Step 1: Append the CREATE TABLE block to `db/zm_create.sql.in`**

Find a sensible location — after the last existing CREATE TABLE statement (alphabetical or near related tables). Insert:

```sql
--
-- Table structure for table `EncoderTemplates`
--

DROP TABLE IF EXISTS `EncoderTemplates`;
CREATE TABLE `EncoderTemplates` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Encoder` varchar(32) NOT NULL,
  `Name` varchar(64) NOT NULL,
  `Description` text,
  `Params` text NOT NULL DEFAULT '',
  PRIMARY KEY (`Id`),
  KEY `Encoder` (`Encoder`),
  UNIQUE KEY `Encoder_Name` (`Encoder`, `Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Immediately after, add the 14 seed `INSERT IGNORE` statements:

```sql
INSERT IGNORE INTO EncoderTemplates (Encoder, Name, Description, Params) VALUES
('libx264', 'Balanced',
 '1080p recording with reasonable CPU cost. Good default for most cameras.',
 'preset=fast\ncrf=23\ng=30\nprofile=high\npix_fmt=yuv420p'),
('libx264', 'Archival (high quality)',
 'Slow encode for archival storage; substantially smaller files at higher CPU cost.',
 'preset=slow\ncrf=20\ng=30\nprofile=high\npix_fmt=yuv420p'),
('libx264', 'Low CPU',
 'Highest encoding speed for slow CPUs; quality and file size trade off.',
 'preset=ultrafast\ncrf=26\ng=30\nprofile=baseline\npix_fmt=yuv420p'),

('libx265', 'Balanced',
 '1080p HEVC recording with reasonable CPU cost. Significantly smaller files than x264 at similar quality.',
 'preset=fast\ncrf=25\ng=30\nprofile=main\npix_fmt=yuv420p'),
('libx265', 'Archival (high quality)',
 'Slow HEVC encode for archival storage.',
 'preset=slow\ncrf=22\ng=30\nprofile=main\npix_fmt=yuv420p'),
('libx265', 'Low CPU',
 'Highest HEVC encoding speed for slow CPUs.',
 'preset=ultrafast\ncrf=28\ng=30\nprofile=main\npix_fmt=yuv420p'),

('h264_nvenc', 'Balanced',
 '1080p H.264 on NVIDIA GPU; sane vbr+cq defaults, no B-frames for low latency.',
 'preset=p4\nrc=vbr\ncq=23\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),
('h264_nvenc', 'Low Power',
 'Faster preset for thermally-constrained NVIDIA hardware.',
 'preset=p1\nrc=vbr\ncq=26\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),

('hevc_nvenc', 'Balanced',
 '1080p HEVC on NVIDIA GPU; sane vbr+cq defaults, no B-frames.',
 'preset=p4\nrc=vbr\ncq=28\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),
('hevc_nvenc', 'Low Power',
 'Faster preset for thermally-constrained NVIDIA hardware.',
 'preset=p1\nrc=vbr\ncq=30\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),

('h264_vaapi', 'Balanced',
 '1080p H.264 via VA-API (Intel/AMD/Mesa); no B-frames.',
 'rc_mode=CQP\nqp=24\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),
('h264_vaapi', 'Low Power',
 'Lower-quality VA-API encode using the low_power codepath.',
 'rc_mode=CQP\nqp=27\ng=30\nbf=0\nprofile=high\npix_fmt=nv12\nlow_power=1'),

('hevc_vaapi', 'Balanced',
 '1080p HEVC via VA-API; no B-frames.',
 'rc_mode=CQP\nqp=27\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),
('hevc_vaapi', 'Low Power',
 'Lower-quality HEVC VA-API encode using the low_power codepath.',
 'rc_mode=CQP\nqp=30\ng=30\nbf=0\nprofile=main\npix_fmt=nv12\nlow_power=1');
```

- [ ] **Step 2: Create `db/zm_update-1.39.6.sql`**

Mirror the same CREATE TABLE + INSERT IGNORE statements (the same content as Step 1). Wrap each statement so existing-installs upgrade idempotently:

```sql
--
-- Add EncoderTemplates table for v2 of the encoder-templates feature.
--

CREATE TABLE IF NOT EXISTS `EncoderTemplates` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Encoder` varchar(32) NOT NULL,
  `Name` varchar(64) NOT NULL,
  `Description` text,
  `Params` text NOT NULL DEFAULT '',
  PRIMARY KEY (`Id`),
  KEY `Encoder` (`Encoder`),
  UNIQUE KEY `Encoder_Name` (`Encoder`, `Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO EncoderTemplates (Encoder, Name, Description, Params) VALUES
-- ... same 14 rows as Step 1 ...
;
```

(Copy the seed block from Step 1 exactly; the `INSERT IGNORE` ensures it's safe to run on an installation that has already been seeded by a prior migration attempt.)

- [ ] **Step 3: Bump `version.txt` to 1.39.6**

```bash
echo "1.39.6" > version.txt
```

- [ ] **Step 4: Verify SQL syntax**

```bash
mysql -h unicron -u root -p --execute "EXPLAIN $(cat <<EOF
CREATE TABLE _encodertemplates_test (
  Id int(10) unsigned NOT NULL AUTO_INCREMENT,
  Encoder varchar(32) NOT NULL,
  Name varchar(64) NOT NULL,
  Description text,
  Params text NOT NULL DEFAULT '',
  PRIMARY KEY (Id),
  KEY Encoder (Encoder),
  UNIQUE KEY Encoder_Name (Encoder, Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DROP TABLE _encodertemplates_test;
EOF
)"
```

(If the user's not OK running ad-hoc SQL on `unicron` — the MySQL host per the project memory — skip the EXPLAIN test and rely on `zm_update.pl` exercise in Step 5.)

- [ ] **Step 5: Run the migration on a test DB**

```bash
sudo -u www-data /usr/bin/zmupdate.pl --version=1.39.6 --user=root --pass=PASSWORD
```

(See project memory: scripts run as `www-data` to read `zm.conf`.) Confirm:
```bash
mysql -h unicron -u zmuser -p zm -e "SELECT COUNT(*) FROM EncoderTemplates;"
```
Returns 14.

- [ ] **Step 6: Commit**

```bash
git add db/zm_create.sql.in db/zm_update-1.39.6.sql version.txt
git commit -m "$(cat <<'EOF'
feat: add EncoderTemplates table and seed v1 templates refs #<N>

The v1 templates lived in a hand-curated PHP array. v2 moves them to
a new EncoderTemplates DB table so they're editable through the UI
and REST API. Seed the same 14 templates verbatim, with new
descriptions captured from the spec. The migration uses
INSERT IGNORE so partial-prior-seed states don't break upgrade.

UNIQUE (Encoder, Name) supports the case-insensitive cross-encoder
name match introduced in the previous commit.

version.txt -> 1.39.6.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Rewrite `EncoderTemplates.php` as DB-backed wrapper

**Files:**
- Modify: `web/includes/EncoderTemplates.php`

- [ ] **Step 1: Replace the file with the DB-backed implementation**

Current file is the static-array (now `templates`-keyed) v1. Replace its body entirely:

```php
<?php
//
// ZoneMinder Encoder Parameter Templates
// Copyright (C) 2026 ZoneMinder Inc.
//
namespace ZM;

class EncoderTemplates {
  // Static allow-list of recognised AVOption keys per encoder.
  // Used by the advisory lint in monitor-encoder-templates.js to flag
  // keys that ffmpeg will silently ignore. Hand-curated; opaque
  // pass-through options like x264-params/x265-params are listed but
  // their inner sub-options are not (ffmpeg's help text doesn't
  // enumerate them, and runtime introspection is deferred — see spec).
  private const VALID_KEYS = [
    'libx264' => [
      'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
      'bf', 'refs', 'pix_fmt', 'x264-params', 'x264opts',
    ],
    'libx265' => [
      'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
      'bf', 'refs', 'pix_fmt', 'x265-params',
    ],
    'h264_nvenc' => [
      'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
      'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info',
    ],
    'hevc_nvenc' => [
      'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
      'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
      'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info', 'tier',
    ],
    'h264_vaapi' => [
      'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
      'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval',
    ],
    'hevc_vaapi' => [
      'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
      'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval', 'tier',
    ],
  ];

  // Returns the templates dict consumed by monitor.js.php. Shape:
  //   { encoder: { valid_keys: [...], templates: [...] }, ... }
  // valid_keys come from VALID_KEYS; templates come from the DB.
  public static function all(): array {
    $byEncoder = [];
    foreach (self::VALID_KEYS as $enc => $keys) {
      $byEncoder[$enc] = ['valid_keys' => $keys, 'templates' => []];
    }
    $rows = dbFetchAll('SELECT Id, Encoder, Name, Description, Params FROM EncoderTemplates ORDER BY Encoder, Name');
    foreach ($rows as $row) {
      $enc = $row['Encoder'];
      if (!isset($byEncoder[$enc])) {
        // Unknown encoder (e.g. user added a row for libsvtav1).
        // No valid_keys -> lint says nothing.
        $byEncoder[$enc] = ['valid_keys' => [], 'templates' => []];
      }
      $byEncoder[$enc]['templates'][] = [
        'id'          => (int)$row['Id'],
        'name'        => $row['Name'],
        'description' => $row['Description'] ?? '',
        'params'      => self::paramsTextToObject($row['Params']),
      ];
    }
    return $byEncoder;
  }

  public static function validKeysFor(string $encoder): array {
    return self::VALID_KEYS[$encoder] ?? [];
  }

  // Convert "key=value\nkey=value" text to {key: value} for the
  // JS module's mergeParams. Mirrors the parseParams JS function.
  private static function paramsTextToObject(string $text): array {
    $out = [];
    foreach (preg_split('/[#,\n]/', $text) as $pair) {
      $idx = strpos($pair, '=');
      if ($idx === false) continue;
      $key = trim(substr($pair, 0, $idx));
      $val = trim(substr($pair, $idx + 1));
      if ($key !== '') $out[$key] = $val;
    }
    return $out;
  }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l web/includes/EncoderTemplates.php
```

Expected: clean.

- [ ] **Step 3: Test that it returns the expected shape**

```bash
php -r '
require "web/includes/Logger.php";
require "web/includes/database.php";
require "web/includes/EncoderTemplates.php";
$d = ZM\EncoderTemplates::all();
echo "encoders: ", count($d), "\n";
echo "libx264 templates: ", count($d["libx264"]["templates"]), "\n";
echo "first libx264 template name: ", $d["libx264"]["templates"][0]["name"], "\n";
echo "first libx264 template params keys: ", implode(",", array_keys($d["libx264"]["templates"][0]["params"])), "\n";
'
```

Expected:
```
encoders: 6
libx264 templates: 3
first libx264 template name: Archival (high quality)
first libx264 template params keys: preset,crf,g,profile,pix_fmt
```

(The first one alphabetically-by-name on libx264 is "Archival (high quality)" because the SQL `ORDER BY Encoder, Name` puts A before B.)

If `database.php` requires more setup to bootstrap from CLI, fall back to a browser-based check after the form integration is in place — load the monitor edit page and inspect `window.ZM_ENCODER_TEMPLATES` in DevTools.

- [ ] **Step 4: Commit**

```bash
git add web/includes/EncoderTemplates.php
git commit -m "$(cat <<'EOF'
refactor: rewrite EncoderTemplates.php as DB-backed wrapper refs #<N>

ZM\EncoderTemplates::all() now reads templates from the DB (seeded by
the v1->v2 migration) rather than holding a hand-curated array.
valid_keys remains a static private constant — it's ffmpeg vocabulary,
not user data, and runtime introspection is deferred.

Adds a paramsTextToObject helper that mirrors the JS parseParams
semantics so the dict shape consumed by monitor.js.php is unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: CakePHP Model

**Files:**
- Create: `web/api/app/Model/EncoderTemplate.php`

- [ ] **Step 1: Write the model**

```php
<?php
App::uses('AppModel', 'Model');

class EncoderTemplate extends AppModel {
  public $useTable = 'EncoderTemplates';
  public $primaryKey = 'Id';
  public $displayField = 'Name';
  public $recursive = -1;

  public $validate = array(
    'Encoder' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Encoder is required',
      ),
    ),
    'Name' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Name is required',
        'last' => true,
      ),
      'unique' => array(
        'rule' => array('isUniqueByEncoder'),
        'message' => 'A template with that name already exists for this encoder',
      ),
    ),
  );

  // Custom validator: Name must be unique within the row's Encoder.
  public function isUniqueByEncoder($field) {
    $name = $field['Name'];
    $encoder = isset($this->data[$this->alias]['Encoder']) ? $this->data[$this->alias]['Encoder'] : null;
    if (!$encoder) return true; // notBlank validator on Encoder will fail separately
    $conditions = array(
      'EncoderTemplate.Encoder' => $encoder,
      'EncoderTemplate.Name' => $name,
    );
    if (!empty($this->id)) {
      $conditions['EncoderTemplate.Id !='] = $this->id;
    }
    return $this->find('count', array('conditions' => $conditions)) === 0;
  }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l web/api/app/Model/EncoderTemplate.php
```

Expected: clean.

- [ ] **Step 3: Commit**

```bash
git add web/api/app/Model/EncoderTemplate.php
git commit -m "$(cat <<'EOF'
feat: add EncoderTemplate CakePHP model refs #<N>

Standard CakePHP 2.x model on the EncoderTemplates table. Validates
Encoder and Name as not blank, and Name as unique-per-Encoder via a
custom validator (CakePHP's built-in isUnique only handles single
columns).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: CakePHP Controller

**Files:**
- Create: `web/api/app/Controller/EncoderTemplatesController.php`

- [ ] **Step 1: Write the controller**

Mirrors `ManufacturersController.php` shape — full CRUD with `System`-priv gating on writes:

```php
<?php
App::uses('AppController', 'Controller');

class EncoderTemplatesController extends AppController {

  public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
  }

  public function index() {
    $this->EncoderTemplate->recursive = -1;
    $conditions = array();
    if (!empty($this->request->query['Encoder'])) {
      $conditions['EncoderTemplate.Encoder'] = $this->request->query['Encoder'];
    }
    $templates = $this->EncoderTemplate->find('all', array(
      'conditions' => $conditions,
      'order' => array('EncoderTemplate.Encoder' => 'ASC', 'EncoderTemplate.Name' => 'ASC'),
    ));
    $this->set(array(
      'encoderTemplates' => $templates,
      '_serialize' => array('encoderTemplates'),
    ));
  }

  public function view($id = null) {
    $this->EncoderTemplate->recursive = -1;
    if (!$this->EncoderTemplate->exists($id)) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $template = $this->EncoderTemplate->find('first', array(
      'conditions' => array('EncoderTemplate.Id' => $id),
    ));
    $this->set(array(
      'encoderTemplate' => $template,
      '_serialize' => array('encoderTemplate'),
    ));
  }

  public function add() {
    if ($this->request->is('post')) {
      global $user;
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if (!$canEdit) {
        throw new UnauthorizedException(__('Insufficient privileges'));
      }
      $this->EncoderTemplate->create();
      if ($this->EncoderTemplate->save($this->request->data)) {
        $this->set(array(
          'message' => 'Saved',
          'id'      => $this->EncoderTemplate->id,
          '_serialize' => array('message', 'id'),
        ));
      } else {
        $this->response->statusCode(422);
        $this->set(array(
          'message' => 'Error',
          'errors'  => $this->EncoderTemplate->validationErrors,
          '_serialize' => array('message', 'errors'),
        ));
      }
    }
  }

  public function edit($id = null) {
    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
    if (!$this->EncoderTemplate->exists($id)) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $this->EncoderTemplate->id = $id;
    if ($this->EncoderTemplate->save($this->request->data)) {
      $this->set(array(
        'message' => 'Saved',
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(422);
      $this->set(array(
        'message' => 'Error',
        'errors'  => $this->EncoderTemplate->validationErrors,
        '_serialize' => array('message', 'errors'),
      ));
    }
  }

  public function delete($id = null) {
    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
    $this->EncoderTemplate->id = $id;
    if (!$this->EncoderTemplate->exists()) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $this->request->allowMethod('post', 'delete');
    if ($this->EncoderTemplate->delete()) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(500);
      $this->set(array(
        'message' => 'Error',
        '_serialize' => array('message'),
      ));
    }
  }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l web/api/app/Controller/EncoderTemplatesController.php
```

Expected: clean.

- [ ] **Step 3: Smoke-test via curl on pseudo (after deploy)**

This step blocks on Task 14's deployment, so deferred. The implementer can come back to it after the editor is in place.

- [ ] **Step 4: Commit**

```bash
git add web/api/app/Controller/EncoderTemplatesController.php
git commit -m "$(cat <<'EOF'
feat: add EncoderTemplates REST API controller refs #<N>

CakePHP 2.x controller mirroring ManufacturersController. Full CRUD:
index/view at viewer level (so the monitor edit page populates),
add/edit/delete gated on System=Edit privilege. index supports
filtering by ?Encoder=<name>. Validation errors return 422 with the
model's validationErrors array under the 'errors' key.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Options menu integration + page tab

**Files:**
- Modify: `web/skins/classic/views/options.php`
- Modify: `web/skins/classic/views/_options_menu.php`
- Create: `web/skins/classic/views/_options_encoderTemplates.php` (skeleton — full UI lands in next task)

- [ ] **Step 1: Add the new tab to the Options menu**

In `web/skins/classic/views/_options_menu.php`, find the `<ul class="nav-tabs">` block and add a new entry near other Options-related items (alphabetically: between something like `Storage` and `Users`):

```php
<li><a class="nav-link <?php echo $tab=='encoderTemplates' ? 'active' : '' ?>"
       href="?view=options&amp;tab=encoderTemplates"><?php echo translate('EncoderTemplates') ?></a></li>
```

Match the surrounding indentation. The exact location depends on where the menu lists tabs — read the file before adding.

- [ ] **Step 2: Add the dispatch branch in options.php**

In `web/skins/classic/views/options.php`, find the chain of `} else if ($tab == 'X') { ... }` blocks (around line 106 where `control` is dispatched to `_options_controlcaps.php`) and add:

```php
} else if ($tab == 'encoderTemplates') {
  if (canView('System')) {
    include('_options_encoderTemplates.php');
  } else {
    $redirect = '?view=error';
    header('Location: '.$redirect);
  }
```

(Match existing brace style.)

- [ ] **Step 3: Create a skeleton `_options_encoderTemplates.php`**

```php
<?php
//
// ZoneMinder Options - Encoder Templates tab
//
require_once('includes/EncoderTemplates.php');
?>
<div id="options">
  <h2><?php echo translate('EncoderTemplates') ?></h2>
  <p><?php echo translate('EncoderTemplatesDescription') ?></p>
  <!-- Editor table + modal land in the next task -->
</div>
```

The translation keys `EncoderTemplates` and `EncoderTemplatesDescription` are added in Task 12 (the translation sweep).

- [ ] **Step 4: Verify and commit**

```bash
php -l web/skins/classic/views/options.php web/skins/classic/views/_options_menu.php web/skins/classic/views/_options_encoderTemplates.php
```

All clean.

```bash
git add web/skins/classic/views/options.php web/skins/classic/views/_options_menu.php web/skins/classic/views/_options_encoderTemplates.php
git commit -m "$(cat <<'EOF'
feat: add EncoderTemplates Options tab skeleton refs #<N>

New top-level Options tab dispatched from options.php's tab chain to
a new _options_encoderTemplates.php include, gated on canView('System').
The next commit fills in the editor UI (table + modal); this commit is
just the navigation plumbing so the menu link works.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Editor table view

**Files:**
- Modify: `web/skins/classic/views/_options_encoderTemplates.php`

- [ ] **Step 1: Replace the skeleton with the table view**

Use the `_options_servers.php` table shape, adapted for our columns. The table is bootstrap-table-driven with `data-click-to-select` for multi-select-and-delete and per-row Edit/Copy buttons. Replace the file body:

```php
<?php
//
// ZoneMinder Options - Encoder Templates tab
//
require_once('includes/EncoderTemplates.php');

$encoderFilter = isset($_REQUEST['encoderFilter']) ? $_REQUEST['encoderFilter'] : '';
$canEdit = canEdit('System');
$dict = ZM\EncoderTemplates::all();

# Hardcoded encoder list — same as monitor.php
$encoders = array(
  '' => translate('AllEncoders'),
  'libx264' => 'libx264',
  'libx265' => 'libx265',
  'h264_nvenc' => 'h264_nvenc',
  'hevc_nvenc' => 'hevc_nvenc',
  'h264_vaapi' => 'h264_vaapi',
  'hevc_vaapi' => 'hevc_vaapi',
);
?>
<form name="encoderTemplatesForm" method="post" action="?">
  <div id="options">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="tab" value="<?php echo $tab ?>"/>

    <div class="col">
      <label for="encoderFilter"><?php echo translate('FilterByEncoder') ?></label>
      <?php echo htmlSelect('encoderFilter', $encoders, $encoderFilter, array('id'=>'encoderFilter')) ?>
    </div>

    <div class="col button-block">
      <div id="contentButtons">
        <button type="button" id="NewEncoderTemplateBtn"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('AddNewEncoderTemplate') ?></button>
      </div>
    </div>

    <div class="wrapper-scroll-table">
      <div class="col">
        <table id="contentTable" class="table table-striped"
            data-click-to-select="true"
            data-show-export="true"
            data-show-columns="true"
            data-cookie="true"
            data-cookie-id-table="zmEncoderTemplatesTable"
            data-cookie-expire="2y"
        >
          <thead class="thead-highlight">
            <tr>
              <th data-sortable="true" class="colId"><?php echo translate('Id') ?></th>
              <th data-sortable="true" class="colEncoder"><?php echo translate('Encoder') ?></th>
              <th data-sortable="true" class="colName"><?php echo translate('Name') ?></th>
              <th data-sortable="true" class="colParams"><?php echo translate('Params') ?></th>
              <th class="colActions"><?php echo translate('Actions') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach ($dict as $enc => $entry) {
  foreach ($entry['templates'] as $tmpl) {
    $shortParams = mb_strimwidth(implode(' ', array_map(
        fn($k, $v) => $k.'='.$v, array_keys($tmpl['params']), array_values($tmpl['params']))),
        0, 60, '…');
?>
            <tr data-tid="<?php echo $tmpl['id'] ?>" data-encoder="<?php echo validHtmlStr($enc) ?>" data-name="<?php echo validHtmlStr($tmpl['name']) ?>">
              <td class="colId"><?php echo $tmpl['id'] ?></td>
              <td class="colEncoder"><?php echo validHtmlStr($enc) ?></td>
              <td class="colName"><?php echo validHtmlStr($tmpl['name']) ?></td>
              <td class="colParams" title="<?php echo validHtmlStr(implode("\n", array_map(
                  fn($k, $v) => $k.'='.$v, array_keys($tmpl['params']), array_values($tmpl['params'])))) ?>"><?php echo validHtmlStr($shortParams) ?></td>
              <td class="colActions">
                <button type="button" class="btn-edit" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Edit') ?></button>
                <button type="button" class="btn-copy" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Copy') ?></button>
                <button type="button" class="btn-delete" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Delete') ?></button>
              </td>
            </tr>
<?php
  }
}
?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>

<!-- Edit modal placeholder; populated client-side -->
<div id="encoderTemplateModalContainer"></div>

<script>
window.ZM_ENCODER_TEMPLATES = <?php echo json_encode(ZM\EncoderTemplates::all(), JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo cache_bust('skins/classic/views/js/monitor-encoder-templates.js') ?>"></script>
<script src="<?php echo cache_bust('skins/classic/views/js/options-encoder-templates.js') ?>"></script>
```

(`window.ZM_ENCODER_TEMPLATES` is dumped in an inline script. Per the v1 CSP fix, that needs a nonce — but options-tab pages already use a different pattern; verify by reading the surrounding pattern in `_options_servers.php` or whether `xhtmlFooter`'s auto-include picks up `views/js/options.js.php`. If the inline tag fails CSP here too, move the dump into a new `web/skins/classic/views/js/options.js.php` or extend `_options_encoderTemplates.php` to use `nonce="<?php echo $cspNonce ?>"`.)

- [ ] **Step 2: Verify**

```bash
php -l web/skins/classic/views/_options_encoderTemplates.php
```

Expected: clean.

- [ ] **Step 3: Commit**

```bash
git add web/skins/classic/views/_options_encoderTemplates.php
git commit -m "$(cat <<'EOF'
feat: render Encoder Templates editor table refs #<N>

Bootstrap-table list of all templates with Encoder filter dropdown,
per-row Edit / Copy / Delete buttons, and a New button. Renders the
templates dict via PHP, dumps it into window.ZM_ENCODER_TEMPLATES for
the JS module's lint integration. The modal HTML and the JS that
wires the buttons land in the next task.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Editor modal + wiring JS

**Files:**
- Create: `web/ajax/modals/encoderTemplate.php`
- Create: `web/skins/classic/views/js/options-encoder-templates.js`

- [ ] **Step 1: Write the modal HTML**

Mirrors `web/ajax/modals/server.php`. Returns the modal markup; the JS injects it into `#encoderTemplateModalContainer` and shows it via Bootstrap.

```php
<?php
// Modal for the EncoderTemplates editor.
// Called via /index.php?view=request&request=modal&modal=encoderTemplate&id=<id>
//   or with id=0 / no id for create mode.

if (!canEdit('System')) {
  ajaxError('Insufficient privileges');
  return;
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$tmpl = null;
if ($id) {
  $row = dbFetchOne('SELECT * FROM EncoderTemplates WHERE Id = ?', null, array($id));
  if (!$row) {
    ajaxError('Invalid template id');
    return;
  }
  $tmpl = $row;
}

$encoders = array(
  'libx264' => 'libx264',
  'libx265' => 'libx265',
  'h264_nvenc' => 'h264_nvenc',
  'hevc_nvenc' => 'hevc_nvenc',
  'h264_vaapi' => 'h264_vaapi',
  'hevc_vaapi' => 'hevc_vaapi',
);
?>
<div class="modal fade" id="EncoderTemplateModal" tabindex="-1" aria-labelledby="encoderTemplateModalTitle" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="encoderTemplateModalTitle">
          <?php echo $tmpl ? translate('EditEncoderTemplate') : translate('NewEncoderTemplate') ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="encoderTemplateError" class="alert alert-danger" style="display:none"></div>
        <input type="hidden" id="EtId" value="<?php echo $tmpl ? $tmpl['Id'] : '' ?>"/>
        <div class="form-group">
          <label for="EtEncoder"><?php echo translate('Encoder') ?></label>
          <?php echo htmlSelect('EtEncoder', $encoders, $tmpl ? $tmpl['Encoder'] : 'libx264', array('id'=>'EtEncoder', $tmpl ? 'disabled' : '_' => 'disabled')) ?>
        </div>
        <div class="form-group">
          <label for="EtName"><?php echo translate('Name') ?></label>
          <input type="text" id="EtName" maxlength="64" value="<?php echo $tmpl ? validHtmlStr($tmpl['Name']) : '' ?>"/>
        </div>
        <div class="form-group">
          <label for="EtDescription"><?php echo translate('Description') ?></label>
          <textarea id="EtDescription" rows="2"><?php echo $tmpl ? validHtmlStr($tmpl['Description'] ?? '') : '' ?></textarea>
        </div>
        <div class="form-group">
          <label for="EtParams"><?php echo translate('Params') ?></label>
          <textarea id="EtParams" rows="6"><?php echo $tmpl ? validHtmlStr($tmpl['Params']) : '' ?></textarea>
          <div id="EtParamsDiagnostics" class="encoderParameterDiagnostics"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button type="button" class="btn btn-primary" id="EtSave"><?php echo translate('Save') ?></button>
      </div>
    </div>
  </div>
</div>
```

(Note: the `htmlSelect`'s `disabled` attribute pass-through uses the existing 4th-arg hack that the v1 monitor.php fix demonstrated; we may need to render the disabled attribute manually instead of trying to drive it through `htmlSelect`. If the helper doesn't handle it cleanly, switch to literal `<select id="EtEncoder" <?php if ($tmpl) echo 'disabled' ?>>...<?php foreach ... ?></select>`.)

- [ ] **Step 2: Register the modal**

In `web/ajax/modal.php` (the dispatch shim), find the existing list of valid modal names and add `'encoderTemplate'`. (The exact mechanism depends on the file's structure — read it first.)

- [ ] **Step 3: Write the editor JS**

Create `web/skins/classic/views/js/options-encoder-templates.js`:

```javascript
(function() {
  'use strict';

  function api(method, url, data, onSuccess, onError) {
    const opts = {
      url: url,
      method: method,
      dataType: 'json',
    };
    if (data) opts.data = data;
    $j.ajax(opts).done(onSuccess).fail(function(jqXHR) {
      onError(jqXHR.responseJSON || {message: jqXHR.statusText});
    });
  }

  function showModal(id) {
    const url = thisUrl + '?view=request&request=modal&modal=encoderTemplate' + (id ? '&id=' + id : '');
    $j.get(url).done(function(html) {
      $j('#encoderTemplateModalContainer').html(html);
      const $modal = $j('#EncoderTemplateModal');
      $modal.modal('show');
      bindModal(id);
    });
  }

  function bindModal(editingId) {
    $j('#EtParams').on('input', runLint);
    $j('#EtEncoder').on('change', runLint);
    $j('#EtSave').on('click', function() { saveModal(editingId); });
    runLint();
  }

  function runLint() {
    const encoder = $j('#EtEncoder').val();
    const text = $j('#EtParams').val();
    const parsed = ZM_EncoderTemplates.parseParams(text);
    const unknown = ZM_EncoderTemplates.lint(parsed, encoder, window.ZM_ENCODER_TEMPLATES);
    const $diag = $j('#EtParamsDiagnostics');
    if (!unknown.length) {
      $diag.hide().text('');
      return;
    }
    $diag.show().text('Note: ' + unknown.map(function(k) { return '`' + k + '`'; }).join(', ')
        + ' are not recognised options for `' + encoder + '` and will be ignored at runtime.');
  }

  function saveModal(editingId) {
    const data = {
      'EncoderTemplate[Encoder]':     $j('#EtEncoder').val(),
      'EncoderTemplate[Name]':        $j('#EtName').val(),
      'EncoderTemplate[Description]': $j('#EtDescription').val(),
      'EncoderTemplate[Params]':      $j('#EtParams').val(),
    };
    const url = '/api/encoderTemplates' + (editingId ? '/' + editingId : '') + '.json';
    const method = editingId ? 'PUT' : 'POST';
    api(method, url, data, function(resp) {
      if (resp.message === 'Saved') {
        $j('#EncoderTemplateModal').modal('hide');
        location.reload(); // simplest; the table re-renders from PHP next load
      } else {
        showError(resp);
      }
    }, showError);
  }

  function showError(resp) {
    const $err = $j('#encoderTemplateError');
    if (resp.errors) {
      const lines = [];
      for (const field in resp.errors) {
        for (const msg of resp.errors[field]) lines.push(field + ': ' + msg);
      }
      $err.text(lines.join('\n')).show();
    } else {
      $err.text(resp.message || 'Unknown error').show();
    }
  }

  function deleteRow(id, name, encoder) {
    if (!window.confirm("Delete template '" + name + "' for " + encoder + '?')) return;
    api('DELETE', '/api/encoderTemplates/' + id + '.json', null, function(resp) {
      if (resp.message === 'Deleted') location.reload();
      else showError(resp);
    }, showError);
  }

  function init() {
    $j('#NewEncoderTemplateBtn').on('click', function() { showModal(null); });
    $j('#contentTable').on('click', '.btn-edit', function() {
      showModal(parseInt($j(this).data('tid'), 10));
    });
    $j('#contentTable').on('click', '.btn-copy', function() {
      const id = parseInt($j(this).data('tid'), 10);
      // Copy = open create modal with prefilled fields. Simplest path:
      // open the modal with id (it loads the full row), then once shown,
      // null out the id and append " Copy" to the name.
      showModal(id);
      // Wait for modal-shown event before patching:
      $j('#EncoderTemplateModal').one('shown.bs.modal', function() {
        $j('#EtId').val('');
        $j('#EtName').val($j('#EtName').val() + ' Copy');
        // Re-enable the encoder select since this is now create mode
        $j('#EtEncoder').prop('disabled', false);
        // Re-bind save with editingId = null
        $j('#EtSave').off('click').on('click', function() { saveModal(null); });
      });
    });
    $j('#contentTable').on('click', '.btn-delete', function() {
      const $row = $j(this).closest('tr');
      deleteRow(parseInt($row.data('tid'), 10), $row.data('name'), $row.data('encoder'));
    });
    $j('#encoderFilter').on('change', function() {
      const enc = $j(this).val();
      $j('#contentTable tbody tr').each(function() {
        $j(this).toggle(!enc || $j(this).data('encoder') === enc);
      });
    });
  }

  $j(document).ready(init);
})();
```

- [ ] **Step 4: Lint and commit**

```bash
npx eslint web/skins/classic/views/js/options-encoder-templates.js
php -l web/ajax/modals/encoderTemplate.php
```

```bash
git add web/ajax/modals/encoderTemplate.php web/skins/classic/views/js/options-encoder-templates.js web/ajax/modal.php
git commit -m "$(cat <<'EOF'
feat: encoder-templates editor modal + button wiring refs #<N>

Bootstrap modal that loads via /index.php?view=request&request=modal
&modal=encoderTemplate&id=<id>, and a small jQuery module that wires
the New / Edit / Copy / Delete buttons to the REST API. Reuses the
v1 lint helper from window.ZM_EncoderTemplates for the in-modal
Params textarea diagnostics.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Translation strings sweep

**Files:**
- Modify: `web/lang/en_gb.php`

- [ ] **Step 1: Add the v2 keys**

Add (alphabetically placed within the `$SLANG` array):

```php
'AddNewEncoderTemplate' => 'Add New Template',
'AllEncoders'           => 'All Encoders',
'EditEncoderTemplate'   => 'Edit Encoder Template',
'EncoderTemplates'      => 'Encoder Templates',
'EncoderTemplatesDescription' => 'Curated parameter sets for ffmpeg encoders. Apply one to a monitor\'s Encoder Parameters from the monitor edit page.',
'FilterByEncoder'       => 'Filter by Encoder',
'NewEncoderTemplate'    => 'New Encoder Template',
'Params'                => 'Params',
```

(Some keys like `Edit`, `Copy`, `Delete`, `Encoder`, `Name`, `Description`, `Save`, `Cancel`, `Id`, `Actions` likely already exist; verify with `grep -n` before adding duplicates.)

- [ ] **Step 2: Verify**

```bash
php -l web/lang/en_gb.php
grep -nE "EncoderTemplates|FilterByEncoder|AllEncoders|NewEncoderTemplate" web/lang/en_gb.php
```

- [ ] **Step 3: Commit**

```bash
git add web/lang/en_gb.php
git commit -m "$(cat <<'EOF'
feat: add v2 encoder-templates translation strings refs #<N>

EncoderTemplates page title, description, filter dropdown labels,
modal titles, and button labels.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: Manual e2e verification on pseudo

**Files:** none (browser testing).

- [ ] **Step 1: Deploy the changeset**

Deploy these files to `/usr/share/zoneminder/www`:

```
db/zm_update-1.39.6.sql                                           → /usr/share/zoneminder/db/
db/zm_create.sql.in                                               → (only relevant for fresh installs; skip for upgrade test)
version.txt                                                       → /usr/share/zoneminder/
web/includes/EncoderTemplates.php                                 → www/includes/
web/api/app/Model/EncoderTemplate.php                             → www/api/app/Model/
web/api/app/Controller/EncoderTemplatesController.php             → www/api/app/Controller/
web/skins/classic/views/options.php                               → www/skins/classic/views/
web/skins/classic/views/_options_menu.php                         → www/skins/classic/views/
web/skins/classic/views/_options_encoderTemplates.php             → www/skins/classic/views/
web/skins/classic/views/monitor.php                               → www/skins/classic/views/
web/skins/classic/views/js/monitor-encoder-templates.js           → www/skins/classic/views/js/
web/skins/classic/views/js/monitor.js.php                         → www/skins/classic/views/js/
web/skins/classic/views/js/options-encoder-templates.js           → www/skins/classic/views/js/
web/ajax/modals/encoderTemplate.php                               → www/ajax/modals/
web/ajax/modal.php                                                → www/ajax/
web/lang/en_gb.php                                                → www/lang/
```

Also delete the v1 `monitor-encoder-presets.js` from the deployed tree if a stale copy lingers.

Then run the migration:
```bash
sudo -u www-data zmupdate.pl --version=1.39.6
```

- [ ] **Step 2: Verify the migration**

Confirm `EncoderTemplates` table exists with 14 rows:
```bash
mysql -h unicron -u zmuser -p zm -e "SELECT Encoder, Name FROM EncoderTemplates ORDER BY Encoder, Name"
```

- [ ] **Step 3: Verify monitor edit page still works**

Open https://pseudo.connortechnology.com/?view=monitor&mid=4 in Chrome. Run the v1 e2e checklist again — Apply preset, lint, cross-encoder dialog. The cross-encoder dialog now matches by Name (case-insensitive) instead of kind. All seven v1 cases must still pass.

- [ ] **Step 4: Verify the editor**

Open https://pseudo.connortechnology.com/?view=options&tab=encoderTemplates. Walk through:

1. The table renders all 14 seeded templates with Encoder/Name/Params columns.
2. Filter dropdown narrows the table to a single encoder; clearing it shows all again.
3. Click Edit on a row → modal opens populated with that row's fields. Encoder dropdown disabled.
4. Make a small Description edit, click Save → modal closes, page reloads, new value visible.
5. Click Copy on the same row → modal opens with `<name> Copy` and the encoder dropdown re-enabled. Click Save → new row appears in table.
6. Click Delete on the new "Copy" row → native confirm fires → row removed.
7. Click New → empty modal. Pick Encoder = libx264, Name = "Test Custom", Description = "test", Params = `preset=fast`. Save. New row appears.
8. Try to save a duplicate (same Encoder + Name as an existing row): expect 422 with the "name already exists" error visible in the modal.
9. Clean up: delete the test rows.

- [ ] **Step 5: Verify the API directly**

```bash
JWT=...   # obtain via /api/host/login
curl -H "Authorization: Bearer $JWT" https://pseudo.connortechnology.com/api/encoderTemplates.json | jq '.encoderTemplates | length'
```

Expected: `14`.

- [ ] **Step 6: No commit** — pure verification.

---

## Task 13: Squash with v1 + push

This task replaces v1's separate commit on local `master` with a single commit that combines v1 + v2.

- [ ] **Step 1: Confirm clean state**

```bash
git status
git log --oneline master..HEAD
```

Expected: working tree clean, ~12 commits on the v2 branch since the spec was committed.

- [ ] **Step 2: Reset master back to before v1, then squash-merge the combined branch**

```bash
git checkout master
# v1 squashed commit was d58833d2a; the parent is the previous merge.
git reset --hard d58833d2a^   # back to 1413362d9 (the Range fix merge)
git merge --squash <N>-encoder-templates-editor   # this includes both v1 + v2 work
```

- [ ] **Step 3: Compose the combined commit message**

```bash
git commit -m "$(cat <<'EOF'
feat: encoder parameter templates with editor + REST API closes #4778 closes #<N>

Adds a curated, per-encoder parameter-template library to ZoneMinder:

- Monitor edit page: a new Template row above the EncoderParameters
  textarea offers per-encoder templates (Balanced / Archival / Low
  Power / Low CPU). Apply merges the template's params into the
  textarea, preserving user-only keys. Advisory lint flags option
  keys that aren't recognised for the selected encoder. Switching
  encoders offers a same-name template on the new encoder via a
  native confirm.

- Options page: a new Encoder Templates tab with full CRUD —
  list / edit / copy / delete — backed by a new CakePHP REST API
  at /api/encoderTemplates.

- Storage: a new EncoderTemplates DB table seeded with 14 shipped
  defaults across libx264/libx265/h264_nvenc/hevc_nvenc/h264_vaapi/
  hevc_vaapi. The table is mutable; ZM upgrades do not re-seed
  user-edited rows.

- valid_keys (the lint allow-list) stays in PHP code as ffmpeg
  vocabulary, not user data.

- Default params explicitly include pix_fmt to avoid the yuvj420p
  HEVC HW-decode rejection issue we hit earlier.

No C++ change. The textarea content is parsed by the existing
av_dict_parse_string call in src/zm_videostore.cpp.

Spec:  docs/superpowers/specs/2026-05-02-encoder-templates-editor-design.md
Plan:  docs/superpowers/plans/2026-05-02-encoder-templates-editor.md

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 4: Push**

```bash
git push origin master
```

(GitHub will close issues #4778 (v1) and the v2 issue automatically because of the `closes` references.)

- [ ] **Step 5: Delete the feature branch**

```bash
git branch -d <N>-encoder-templates-editor
```

---

## Self-review notes

- **Spec coverage:** Section 2 (schema) → Task 4. Section 3 (backend API) → Tasks 5/6/7. Section 4 (editor UI) → Tasks 8/9/10. Section 5 (monitor integration) → Tasks 2 + 3 (rename + name-match). Section 6 (renames) → Tasks 2 + 3. Section 7 (out of scope) is not implemented by design.
- **Type/name consistency:** `findTemplateByName`, `applyTemplate`, `repopulateTemplates`, `findTemplate`, `lastAppliedName`, `EncoderTemplate`, `EncoderTemplateRow`, `ApplyEncoderTemplate` are stable across all tasks. The `templates` (not `presets`) inner-array key is consistent.
- **Frequent commits:** 13 commits on the v2 branch (1 spec, 12 feature) before squashing with v1 for the final push. Each independently meaningful.
- **No placeholders:** `<N>` is substituted with the issue number from Task 1; `1.39.6` is concrete.
- **Tests:** the JS pure-function suite extends to 27 tests (existing 23 + 4 for `findTemplateByName`). Editor / modal / API are manually verified — ZM doesn't have automated PHP/CakePHP tests checked in, and bootstrapping that is out of scope for this feature.
