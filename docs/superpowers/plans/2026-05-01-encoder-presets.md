# Encoder Parameter Presets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a per-encoder preset library to the monitor edit page so users can pick a recommended set of ffmpeg AVOptions for their selected encoder, with the picked preset's params merged into the existing free-form `EncoderParameters` textarea and a per-encoder allow-list driving advisory linting of unrecognised keys.

**Architecture:** Pure UI/data-only feature. A static PHP data file under `web/includes/` exports the templates dict; a small browser JS module (UMD-pattern so it's testable in Node) provides four pure functions (`parseParams`, `mergeParams`, `serializeParams`, `lint`) plus DOM wiring. The monitor edit form gets a new "Preset" row above the existing textarea, plus a diagnostics div below it. No DB schema change, no C++ change, no REST endpoint.

**Tech Stack:** PHP 7.4+ (existing ZM web layer), browser JS (jQuery + bootbox already loaded by ZM), Node ≥ 18 for running pure-function tests, ESLint (existing project config).

**Spec:** `docs/superpowers/specs/2026-05-01-encoder-presets-design.md`

---

## Task 1: Open GitHub issue and prepare branch

**Files:**
- (no source changes; git/branch only)

- [ ] **Step 1: Open the GH issue and capture the number**

```bash
gh issue create \
  --title "Encoder parameter presets for monitor edit page" \
  --label enhancement \
  --body "$(cat <<'EOF'
Add a per-encoder preset library to the monitor edit page so the user can pick recommended ffmpeg AVOptions for their selected encoder. Selecting a preset merges its params into the existing free-form EncoderParameters textarea (overwrite on conflict, keep user-only keys). A per-encoder valid-keys list drives advisory linting of unrecognised keys.

Spec: docs/superpowers/specs/2026-05-01-encoder-presets-design.md
EOF
)"
```

Note the returned issue number; substitute it for `<N>` in subsequent commands.

- [ ] **Step 2: Set up the working branch**

The spec commit is currently on `4778-encoder-presets-design` (a placeholder name from before the issue existed). Rename that branch to match the real issue number, then continue on it.

```bash
# Replace <N> with the issue number returned in Step 1
git branch -m 4778-encoder-presets-design <N>-encoder-presets
git status   # confirm we are on the renamed branch with the spec commit on top
```

- [ ] **Step 3: Verify clean state**

```bash
git log --oneline master..HEAD
```

Expected: exactly one commit, the spec doc commit `docs: add encoder presets design spec`.

---

## Task 2: Create EncoderTemplates.php with all six initial encoders

**Files:**
- Create: `web/includes/EncoderTemplates.php`

- [ ] **Step 1: Write the file**

```php
<?php
//
// ZoneMinder Encoder Parameter Presets
// Copyright (C) 2026 ZoneMinder Inc.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
namespace ZM;

class EncoderTemplates {
  public static function all() {
    return [
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
      'libx265' => [
        'valid_keys' => [
          'preset', 'tune', 'profile', 'level', 'crf', 'qp', 'b',
          'maxrate', 'bufsize', 'g', 'keyint_min', 'sc_threshold',
          'bf', 'refs', 'pix_fmt', 'x265-params',
        ],
        'presets' => [
          ['id'=>'libx265_balanced',  'name'=>'Balanced',                'kind'=>'balanced',
           'params'=>['preset'=>'fast',      'crf'=>'25', 'g'=>'30', 'profile'=>'main', 'pix_fmt'=>'yuv420p']],
          ['id'=>'libx265_archival',  'name'=>'Archival (high quality)', 'kind'=>'archival',
           'params'=>['preset'=>'slow',      'crf'=>'22', 'g'=>'30', 'profile'=>'main', 'pix_fmt'=>'yuv420p']],
          ['id'=>'libx265_lowcpu',    'name'=>'Low CPU',                 'kind'=>'low_cpu',
           'params'=>['preset'=>'ultrafast', 'crf'=>'28', 'g'=>'30', 'profile'=>'main', 'pix_fmt'=>'yuv420p']],
        ],
      ],
      'h264_nvenc' => [
        'valid_keys' => [
          'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
          'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
          'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info',
        ],
        'presets' => [
          ['id'=>'h264_nvenc_balanced','name'=>'Balanced',  'kind'=>'balanced',
           'params'=>['preset'=>'p4', 'rc'=>'vbr', 'cq'=>'23', 'g'=>'30', 'bf'=>'0', 'profile'=>'high', 'pix_fmt'=>'nv12']],
          ['id'=>'h264_nvenc_lowpower','name'=>'Low Power', 'kind'=>'low_power',
           'params'=>['preset'=>'p1', 'rc'=>'vbr', 'cq'=>'26', 'g'=>'30', 'bf'=>'0', 'profile'=>'high', 'pix_fmt'=>'nv12']],
        ],
      ],
      'hevc_nvenc' => [
        'valid_keys' => [
          'preset', 'tune', 'profile', 'level', 'rc', 'cq', 'qp', 'b',
          'maxrate', 'bufsize', 'g', 'bf', 'spatial-aq', 'temporal-aq',
          'rc-lookahead', 'pix_fmt', 'gpu', 'tuning_info', 'tier',
        ],
        'presets' => [
          ['id'=>'hevc_nvenc_balanced','name'=>'Balanced',  'kind'=>'balanced',
           'params'=>['preset'=>'p4', 'rc'=>'vbr', 'cq'=>'28', 'g'=>'30', 'bf'=>'0', 'profile'=>'main', 'pix_fmt'=>'nv12']],
          ['id'=>'hevc_nvenc_lowpower','name'=>'Low Power', 'kind'=>'low_power',
           'params'=>['preset'=>'p1', 'rc'=>'vbr', 'cq'=>'30', 'g'=>'30', 'bf'=>'0', 'profile'=>'main', 'pix_fmt'=>'nv12']],
        ],
      ],
      'h264_vaapi' => [
        'valid_keys' => [
          'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
          'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval',
        ],
        'presets' => [
          ['id'=>'h264_vaapi_balanced','name'=>'Balanced',  'kind'=>'balanced',
           'params'=>['rc_mode'=>'CQP', 'qp'=>'24', 'g'=>'30', 'bf'=>'0', 'profile'=>'high', 'pix_fmt'=>'nv12']],
          ['id'=>'h264_vaapi_lowpower','name'=>'Low Power', 'kind'=>'low_power',
           'params'=>['rc_mode'=>'CQP', 'qp'=>'27', 'g'=>'30', 'bf'=>'0', 'profile'=>'high', 'pix_fmt'=>'nv12', 'low_power'=>'1']],
        ],
      ],
      'hevc_vaapi' => [
        'valid_keys' => [
          'profile', 'level', 'rc_mode', 'qp', 'b', 'maxrate', 'bufsize',
          'g', 'bf', 'pix_fmt', 'low_power', 'idr_interval', 'tier',
        ],
        'presets' => [
          ['id'=>'hevc_vaapi_balanced','name'=>'Balanced',  'kind'=>'balanced',
           'params'=>['rc_mode'=>'CQP', 'qp'=>'27', 'g'=>'30', 'bf'=>'0', 'profile'=>'main', 'pix_fmt'=>'nv12']],
          ['id'=>'hevc_vaapi_lowpower','name'=>'Low Power', 'kind'=>'low_power',
           'params'=>['rc_mode'=>'CQP', 'qp'=>'30', 'g'=>'30', 'bf'=>'0', 'profile'=>'main', 'pix_fmt'=>'nv12', 'low_power'=>'1']],
        ],
      ],
    ];
  }
}
```

- [ ] **Step 2: Verify PHP parses cleanly**

Run: `php -l web/includes/EncoderTemplates.php`
Expected: `No syntax errors detected in web/includes/EncoderTemplates.php`

- [ ] **Step 3: Verify JSON-encodes round-trip**

Run:
```bash
php -r 'require "web/includes/EncoderTemplates.php"; $j = json_encode(ZM\EncoderTemplates::all(), JSON_UNESCAPED_SLASHES); $d = json_decode($j, true); echo "encoders: ", count($d), "\n", "first preset id: ", $d["libx264"]["presets"][0]["id"], "\n";'
```
Expected:
```
encoders: 6
first preset id: libx264_balanced
```

- [ ] **Step 4: Commit**

```bash
git add web/includes/EncoderTemplates.php
git commit -m "$(cat <<'EOF'
feat: add EncoderTemplates data file with initial six encoders refs #<N>

ZM\EncoderTemplates::all() returns the per-encoder dict consumed by
the monitor edit page. Initial coverage: libx264, libx265, h264_nvenc,
hevc_nvenc, h264_vaapi, hevc_vaapi. Each entry has a hand-curated
valid_keys allow-list (driving advisory lint) and an ordered presets
array tagged by kind (balanced/archival/low_power/low_cpu/low_latency)
to support cross-encoder same-kind matching.

No callers yet; the JS module and form changes ship in subsequent
commits.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Create the JS module skeleton with parseParams + node-runnable test

**Files:**
- Create: `web/skins/classic/views/js/monitor-encoder-presets.js`
- Create: `tests/js/encoder-presets.test.js`

- [ ] **Step 1: Write the failing test**

Create `tests/js/encoder-presets.test.js`:

```javascript
'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname,
    '../../web/skins/classic/views/js/monitor-encoder-presets.js'));

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

console.log('parseParams');
test('single key=value line', () => {
  assert.deepStrictEqual(
      ZM.parseParams('preset=fast'),
      [{key: 'preset', value: 'fast'}]);
});
test('multiple lines', () => {
  assert.deepStrictEqual(
      ZM.parseParams('preset=fast\ncrf=23'),
      [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('comma separator (av_dict_parse_string semantics)', () => {
  assert.deepStrictEqual(
      ZM.parseParams('preset=fast,crf=23'),
      [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('blank lines are dropped', () => {
  assert.deepStrictEqual(
      ZM.parseParams('preset=fast\n\n\ncrf=23\n'),
      [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('whitespace around key and value is trimmed', () => {
  assert.deepStrictEqual(
      ZM.parseParams('  preset = fast  \n  crf = 23  '),
      [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('lines without = are dropped', () => {
  assert.deepStrictEqual(
      ZM.parseParams('preset=fast\njust_a_word\ncrf=23'),
      [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('value containing = keeps the trailing equals', () => {
  assert.deepStrictEqual(
      ZM.parseParams('x264-params=keyint=30:bframes=0'),
      [{key: 'x264-params', value: 'keyint=30:bframes=0'}]);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node tests/js/encoder-presets.test.js`
Expected: fails with `Cannot find module '.../monitor-encoder-presets.js'`.

- [ ] **Step 3: Write the minimal module with parseParams**

Create `web/skins/classic/views/js/monitor-encoder-presets.js`:

```javascript
//
// ZoneMinder Monitor Encoder Presets
//
// UMD-pattern module: pure functions are exposed as named members on
// window.ZM_EncoderPresets in browsers, and via module.exports in Node
// for unit testing. The DOM wiring (init) only runs in the browser.
//
(function(global) {
  'use strict';

  // Match av_dict_parse_string semantics: pairs separated by any of
  // \n, , (and # which we treat as a separator too — matching ffmpeg).
  // Each pair is "key=value". Unrecognised pairs without = are dropped.
  function parseParams(text) {
    if (!text) return [];
    const out = [];
    const pairs = String(text).split(/[#,\n]/);
    for (const p of pairs) {
      const idx = p.indexOf('=');
      if (idx < 0) continue;
      const key = p.slice(0, idx).trim();
      const value = p.slice(idx + 1).trim();
      if (key) out.push({key: key, value: value});
    }
    return out;
  }

  const api = {
    parseParams: parseParams,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    global.ZM_EncoderPresets = api;
  }
})(typeof window !== 'undefined' ? window : globalThis);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `node tests/js/encoder-presets.test.js`
Expected: all 7 tests pass, exit code 0.

- [ ] **Step 5: Lint**

Run: `npx eslint web/skins/classic/views/js/monitor-encoder-presets.js`
Expected: clean (no output).

- [ ] **Step 6: Commit**

```bash
git add web/skins/classic/views/js/monitor-encoder-presets.js tests/js/encoder-presets.test.js
git commit -m "$(cat <<'EOF'
feat: add encoder-presets JS module with parseParams refs #<N>

UMD-pattern module exposes parseParams (and follow-up siblings) on
window.ZM_EncoderPresets for the browser and module.exports for Node
tests. parseParams splits on \n / , / # to match av_dict_parse_string
semantics in zm_videostore.cpp, trims keys and values, drops malformed
lines.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Add mergeParams + tests

**Files:**
- Modify: `web/skins/classic/views/js/monitor-encoder-presets.js`
- Modify: `tests/js/encoder-presets.test.js`

- [ ] **Step 1: Write failing tests**

Append to `tests/js/encoder-presets.test.js` before the final summary line:

```javascript
console.log('\nmergeParams');
test('overwrite existing key keeps position', () => {
  const existing = [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}];
  const out = ZM.mergeParams(existing, {preset: 'slow'});
  assert.deepStrictEqual(out, [{key: 'preset', value: 'slow'}, {key: 'crf', value: '23'}]);
});
test('append new key when not present', () => {
  const existing = [{key: 'preset', value: 'fast'}];
  const out = ZM.mergeParams(existing, {crf: '23'});
  assert.deepStrictEqual(out, [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}]);
});
test('preserve user-only keys', () => {
  const existing = [{key: 'preset', value: 'fast'}, {key: 'custom_x', value: '1'}];
  const out = ZM.mergeParams(existing, {crf: '23'});
  assert.deepStrictEqual(out, [
    {key: 'preset', value: 'fast'},
    {key: 'custom_x', value: '1'},
    {key: 'crf', value: '23'},
  ]);
});
test('idempotent: applying same template twice', () => {
  const tmpl = {preset: 'slow', crf: '20'};
  const once = ZM.mergeParams(ZM.parseParams(''), tmpl);
  const twice = ZM.mergeParams(once, tmpl);
  assert.deepStrictEqual(once, twice);
});
test('does not mutate input array', () => {
  const existing = [{key: 'preset', value: 'fast'}];
  const out = ZM.mergeParams(existing, {preset: 'slow'});
  assert.strictEqual(existing[0].value, 'fast');
  assert.strictEqual(out[0].value, 'slow');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `node tests/js/encoder-presets.test.js`
Expected: 5 failures with `ZM.mergeParams is not a function`.

- [ ] **Step 3: Add mergeParams to the module**

In `web/skins/classic/views/js/monitor-encoder-presets.js`, immediately after `parseParams`, add:

```javascript
  // Merge templateParams (object) into existing (parseParams output).
  // Keys present in both: existing position retained, value replaced.
  // Keys only in template: appended at the end.
  // Keys only in existing: kept as-is.
  // Returns a new array; does not mutate either input.
  function mergeParams(existing, templateParams) {
    const result = existing.map(function(e) { return {key: e.key, value: e.value}; });
    const positions = {};
    for (let i = 0; i < result.length; i++) positions[result[i].key] = i;
    for (const k in templateParams) {
      if (!Object.prototype.hasOwnProperty.call(templateParams, k)) continue;
      const v = String(templateParams[k]);
      if (k in positions) {
        result[positions[k]].value = v;
      } else {
        positions[k] = result.length;
        result.push({key: k, value: v});
      }
    }
    return result;
  }
```

And expose it in `api`:

```javascript
  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
  };
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `node tests/js/encoder-presets.test.js`
Expected: all 12 tests pass, exit 0.

- [ ] **Step 5: Lint and commit**

```bash
npx eslint web/skins/classic/views/js/monitor-encoder-presets.js
git add web/skins/classic/views/js/monitor-encoder-presets.js tests/js/encoder-presets.test.js
git commit -m "$(cat <<'EOF'
feat: add mergeParams to encoder-presets module refs #<N>

Merges a template's params object into a parsed textarea array,
preserving position for overlapping keys, appending new template keys,
and keeping user-only keys untouched. Idempotent under repeated apply.
Pure (no input mutation).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Add serializeParams + tests

**Files:**
- Modify: `web/skins/classic/views/js/monitor-encoder-presets.js`
- Modify: `tests/js/encoder-presets.test.js`

- [ ] **Step 1: Write failing tests**

Append to `tests/js/encoder-presets.test.js` before the summary line:

```javascript
console.log('\nserializeParams');
test('serializes one entry per line', () => {
  const arr = [{key: 'preset', value: 'fast'}, {key: 'crf', value: '23'}];
  assert.strictEqual(ZM.serializeParams(arr), 'preset=fast\ncrf=23');
});
test('empty array serializes to empty string', () => {
  assert.strictEqual(ZM.serializeParams([]), '');
});
test('round-trip parse -> serialize is stable', () => {
  const t = 'preset=fast\ncrf=23\ng=30';
  assert.strictEqual(ZM.serializeParams(ZM.parseParams(t)), t);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `node tests/js/encoder-presets.test.js`
Expected: 3 failures with `ZM.serializeParams is not a function`.

- [ ] **Step 3: Add serializeParams to the module**

After `mergeParams` in `monitor-encoder-presets.js`:

```javascript
  function serializeParams(arr) {
    if (!arr || !arr.length) return '';
    return arr.map(function(e) { return e.key + '=' + e.value; }).join('\n');
  }
```

Add to `api`:

```javascript
  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
    serializeParams: serializeParams,
  };
```

- [ ] **Step 4: Verify and commit**

```bash
node tests/js/encoder-presets.test.js
npx eslint web/skins/classic/views/js/monitor-encoder-presets.js
git add web/skins/classic/views/js/monitor-encoder-presets.js tests/js/encoder-presets.test.js
git commit -m "feat: add serializeParams to encoder-presets module refs #<N>

$(cat <<'EOF'
serializeParams writes a parsed array back to text in one-per-line
key=value form. parse -> serialize round-trip is stable for input that
already used \n separators.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Add lint + tests

**Files:**
- Modify: `web/skins/classic/views/js/monitor-encoder-presets.js`
- Modify: `tests/js/encoder-presets.test.js`

- [ ] **Step 1: Write failing tests**

Append to `tests/js/encoder-presets.test.js`:

```javascript
console.log('\nlint');
const TEMPLATES_FIXTURE = {
  libx264: {
    valid_keys: ['preset', 'crf', 'g', 'profile', 'pix_fmt'],
    presets: [],
  },
  h264_nvenc: {
    valid_keys: ['preset', 'rc', 'cq', 'g', 'profile', 'pix_fmt'],
    presets: [],
  },
};
test('returns empty list when all keys are valid', () => {
  const parsed = ZM.parseParams('preset=fast\ncrf=23');
  assert.deepStrictEqual(ZM.lint(parsed, 'libx264', TEMPLATES_FIXTURE), []);
});
test('returns unknown keys', () => {
  const parsed = ZM.parseParams('preset=fast\ncrf=23\ntune=zerolatency');
  assert.deepStrictEqual(ZM.lint(parsed, 'libx264', TEMPLATES_FIXTURE), ['tune']);
});
test('reports each unknown key only once', () => {
  const parsed = ZM.parseParams('foo=1\nfoo=2');
  assert.deepStrictEqual(ZM.lint(parsed, 'libx264', TEMPLATES_FIXTURE), ['foo']);
});
test('returns [] for unknown encoder (no opinion)', () => {
  const parsed = ZM.parseParams('anything=here');
  assert.deepStrictEqual(ZM.lint(parsed, 'libsvtav1', TEMPLATES_FIXTURE), []);
});
test('returns [] when encoder is empty/auto', () => {
  const parsed = ZM.parseParams('preset=fast');
  assert.deepStrictEqual(ZM.lint(parsed, 'auto', TEMPLATES_FIXTURE), []);
  assert.deepStrictEqual(ZM.lint(parsed, '', TEMPLATES_FIXTURE), []);
});
```

- [ ] **Step 2: Run tests to verify failures**

Run: `node tests/js/encoder-presets.test.js`
Expected: 5 failures with `ZM.lint is not a function`.

- [ ] **Step 3: Implement lint**

After `serializeParams` in `monitor-encoder-presets.js`:

```javascript
  // Returns the list of keys present in `parsed` that are not in
  // `valid_keys` for the given encoder. Returns [] when the encoder
  // has no entry in `templates` (no opinion).
  function lint(parsed, encoder, templates) {
    if (!encoder || !templates || !templates[encoder]) return [];
    const valid = templates[encoder].valid_keys || [];
    const validSet = {};
    for (let i = 0; i < valid.length; i++) validSet[valid[i]] = true;
    const seen = {};
    const out = [];
    for (let i = 0; i < parsed.length; i++) {
      const k = parsed[i].key;
      if (!validSet[k] && !seen[k]) {
        seen[k] = true;
        out.push(k);
      }
    }
    return out;
  }
```

Add to `api`:

```javascript
  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
    serializeParams: serializeParams,
    lint: lint,
  };
```

- [ ] **Step 4: Verify and commit**

```bash
node tests/js/encoder-presets.test.js
npx eslint web/skins/classic/views/js/monitor-encoder-presets.js
git add web/skins/classic/views/js/monitor-encoder-presets.js tests/js/encoder-presets.test.js
git commit -m "$(cat <<'EOF'
feat: add lint to encoder-presets module refs #<N>

lint(parsed, encoder, templates) returns the list of keys in `parsed`
that aren't in the encoder's valid_keys allow-list. Returns [] when
the encoder has no template entry (treated as 'no opinion'), so
unknown encoders never trigger false positives.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Add DOM wiring (init + helpers)

**Files:**
- Modify: `web/skins/classic/views/js/monitor-encoder-presets.js`

This task adds browser-only code; the pure functions are already covered by node tests. End-to-end browser verification is in Task 11.

- [ ] **Step 1: Append the wiring**

Inside the IIFE in `monitor-encoder-presets.js`, before the `api` object, add:

```javascript
  // ---- DOM wiring (browser only) -----------------------------------------

  let lastAppliedKind = null;

  function getTemplates() {
    return (typeof window !== 'undefined' && window.ZM_ENCODER_TEMPLATES) || {};
  }

  function findPreset(encoder, presetId) {
    const t = getTemplates()[encoder];
    if (!t) return null;
    for (let i = 0; i < t.presets.length; i++) {
      if (t.presets[i].id === presetId) return t.presets[i];
    }
    return null;
  }

  function presetWithKind(encoder, kind) {
    if (!kind) return null;
    const t = getTemplates()[encoder];
    if (!t) return null;
    for (let i = 0; i < t.presets.length; i++) {
      if (t.presets[i].kind === kind) return t.presets[i];
    }
    return null;
  }

  function repopulatePresets() {
    const encoderSel = document.getElementById('ZmEncoder');
    const presetSel = document.getElementById('EncoderPreset');
    const row = document.getElementById('EncoderPresetRow');
    if (!encoderSel || !presetSel || !row) return;

    const encoder = encoderSel.value;
    const t = getTemplates()[encoder];
    presetSel.innerHTML = '';
    if (!t || !t.presets.length) {
      row.style.display = 'none';
      return;
    }
    row.style.display = '';
    for (let i = 0; i < t.presets.length; i++) {
      const p = t.presets[i];
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.name;
      opt.dataset.kind = p.kind;
      presetSel.appendChild(opt);
    }
  }

  function applyPreset(presetId) {
    const encoderSel = document.getElementById('ZmEncoder');
    const textarea = document.getElementById('EncoderParameters');
    if (!encoderSel || !textarea || !presetId) return;
    const preset = findPreset(encoderSel.value, presetId);
    if (!preset) return;
    const merged = mergeParams(parseParams(textarea.value), preset.params);
    textarea.value = serializeParams(merged);
    lastAppliedKind = preset.kind;
    runLint();
  }

  function runLint() {
    const encoderSel = document.getElementById('ZmEncoder');
    const textarea = document.getElementById('EncoderParameters');
    const diag = document.getElementById('EncoderParameterDiagnostics');
    if (!encoderSel || !textarea || !diag) return;
    const unknown = lint(parseParams(textarea.value), encoderSel.value, getTemplates());
    if (!unknown.length) {
      diag.textContent = '';
      diag.style.display = 'none';
      return;
    }
    diag.style.display = '';
    diag.textContent = 'Note: ' + unknown.map(function(k) { return '`' + k + '`'; }).join(', ')
        + ' are not recognised options for `' + encoderSel.value
        + '` and will be ignored at runtime.';
  }

  function onEncoderChange() {
    const encoderSel = document.getElementById('ZmEncoder');
    if (!encoderSel) return;
    const previousKind = lastAppliedKind;
    repopulatePresets();
    runLint();
    if (!previousKind) return;
    const match = presetWithKind(encoderSel.value, previousKind);
    if (!match) return;
    if (typeof bootbox !== 'undefined' && bootbox.confirm) {
      bootbox.confirm({
        title: 'Apply matching preset?',
        message: 'You had a "' + previousKind + '" preset selected. ' +
                 'Apply <strong>' + match.name + '</strong> for ' + encoderSel.value + '?',
        buttons: {
          confirm: {label: 'Apply'},
          cancel: {label: 'Cancel'},
        },
        callback: function(ok) {
          if (ok) applyPreset(match.id);
        },
      });
    }
  }

  function init() {
    if (typeof document === 'undefined') return;
    const encoderSel = document.getElementById('ZmEncoder');
    const presetSel = document.getElementById('EncoderPreset');
    const applyBtn = document.getElementById('ApplyEncoderPreset');
    const textarea = document.getElementById('EncoderParameters');
    if (!encoderSel || !presetSel || !applyBtn || !textarea) return;

    encoderSel.addEventListener('change', onEncoderChange);
    applyBtn.addEventListener('click', function() { applyPreset(presetSel.value); });
    presetSel.addEventListener('change', function() {
      const opt = presetSel.options[presetSel.selectedIndex];
      if (opt && opt.dataset.kind) lastAppliedKind = opt.dataset.kind;
    });
    textarea.addEventListener('input', runLint);

    repopulatePresets();
    runLint();
  }

  if (typeof document !== 'undefined' && document.addEventListener) {
    document.addEventListener('DOMContentLoaded', init);
  }
```

Update the `api` to also expose `init` (for testability and explicit invocation):

```javascript
  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
    serializeParams: serializeParams,
    lint: lint,
    init: init,
  };
```

- [ ] **Step 2: Verify pure-function tests still pass**

Run: `node tests/js/encoder-presets.test.js`
Expected: 20 tests pass (all previous + the lint set), exit 0.

- [ ] **Step 3: Lint**

Run: `npx eslint web/skins/classic/views/js/monitor-encoder-presets.js`
Expected: clean.

- [ ] **Step 4: Commit**

```bash
git add web/skins/classic/views/js/monitor-encoder-presets.js
git commit -m "$(cat <<'EOF'
feat: wire encoder-presets module to monitor edit DOM refs #<N>

DOM glue for the new Preset row on the monitor edit page:
- repopulates the Preset dropdown when Encoder changes
- merges a preset's params into EncoderParameters textarea on Apply
- runs advisory lint reactively on textarea input and encoder change
- offers a same-kind cross-encoder preset via bootbox.confirm when the
  user switches encoders after having applied a preset

Reads window.ZM_ENCODER_TEMPLATES (server-side JSON dump from
EncoderTemplates::all()), no AJAX. Pure functions remain testable
in Node; init only runs in the browser.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Find the existing Encoder select id in monitor.php

**Files:**
- Read: `web/skins/classic/views/monitor.php`

This is a verification step — the JS uses `document.getElementById('ZmEncoder')` and `'EncoderParameters'`. Confirm the existing form elements have those ids before we add the new ones, or adjust the JS module if the existing names differ.

- [ ] **Step 1: Inspect the existing Encoder and EncoderParameters elements**

Run:
```bash
grep -n "newMonitor\[Encoder\]\|EncoderParameters" web/skins/classic/views/monitor.php
```

- [ ] **Step 2: Note the actual ids**

Read the surrounding lines and confirm whether the existing `<select name="newMonitor[Encoder]">` has an `id="ZmEncoder"` (it likely does NOT — `htmlSelect()` may auto-generate an id based on the name, e.g. `id="newMonitor[Encoder]"` which is invalid for getElementById, or no id at all).

If it does not have a usable id, plan to add one in Task 9. The textarea at line ~1225 also needs `id="EncoderParameters"` if it is missing.

- [ ] **Step 3: Record findings**

In a scratch note (not committed), write down:
- Encoder select id: (existing or "to add: ZmEncoder")
- EncoderParameters textarea id: (existing or "to add: EncoderParameters")

These inform the exact edits in Task 9. No commit in this task.

---

## Task 9: Modify monitor.php — render Preset row, ids, JSON dump, script include

**Files:**
- Modify: `web/skins/classic/views/monitor.php`

- [ ] **Step 1: Ensure the Encoder select has `id="ZmEncoder"`**

Locate the existing `htmlSelect('newMonitor[Encoder]', $videowriter_encoders, $monitor->Encoder())` (around line 1196). Replace it with a form using a stable id. The simplest approach is to render the select manually here so we control the id:

```php
            <li class="Encoder">
              <label><?php echo translate('Encoder') ?></label>
              
<?php
$videowriter_encoders = array(
  'auto' => translate('Auto'),
  'libx264' => 'libx264',
  'h264' => 'h264',
  'h264_nvenc' => 'h264_nvenc',
  'h264_omx' => 'h264_omx',
  'h264_qsv' => 'h264_qsv',
  'h264_vaapi' => 'h264_vaapi',
  'h264_v4l2m2m' => 'h264_v4l2m2m',
  'libx265' => 'libx265',
  'hevc_nvenc' => 'hevc_nvenc',
  'hevc_qsv' => 'hevc_qsv',
  'hevc_vaapi' => 'hevc_vaapi',
  'libvpx-vp9' => 'libvpx-vp9',
  'vp9-qsv' => 'vp9-qsv',
  'libsvtav1' => 'libsvtav1',
  'libaom-av1'  => 'libaom-av1',
  'av1_qsv' => 'av1_qsv',
  'av1_vaapi' => 'av1_vaapi',
  'av1_nvenc' => 'av1_nvenc'
);
echo htmlSelect('newMonitor[Encoder]', $videowriter_encoders, $monitor->Encoder(), array('id'=>'ZmEncoder'));
?>
            </li>
```

(`htmlSelect()` accepts a 4th argument of HTML attribute overrides; verify by reading `web/includes/functions.php` for the `htmlSelect` definition. If it doesn't accept overrides, fall back to writing a literal `<select id="ZmEncoder" ...>` here.)

- [ ] **Step 2: Insert the new Preset row immediately after the Encoder `<li>` block**

Right after the closing `</li>` of the Encoder block (and before `<li class="EncoderHWAccelName">`), insert:

```php
            <li class="EncoderPreset" id="EncoderPresetRow">
              <label><?php echo translate('EncoderPreset') ?></label>
              <select id="EncoderPreset" name="newMonitor[__preset_picker]"></select>
              <button type="button" id="ApplyEncoderPreset"><?php echo translate('ApplyEncoderPreset') ?></button>
            </li>
```

- [ ] **Step 3: Move the EncoderParameters `<li>` up**

Currently the order is: Encoder, EncoderHWAccelName, EncoderHWAccelDevice, OutputContainer, EncoderParameters. Move the `<li class="EncoderParameters">` block (around line 1223) to sit immediately after the new Preset row (so order becomes Encoder, EncoderPreset, EncoderParameters, EncoderHWAccelName, …). While moving, ensure the textarea has `id="EncoderParameters"`:

```php
            <li class="EncoderParameters">
              <label><?php echo translate('OptionalEncoderParam'); echo makeHelpLink('OPTIONS_ENCODER_PARAMETERS') ?></label>
              <textarea id="EncoderParameters" name="newMonitor[EncoderParameters]" rows="<?php echo count(explode("\n", $monitor->EncoderParameters())); ?>"><?php echo validHtmlStr($monitor->EncoderParameters()) ?></textarea>
              <div id="EncoderParameterDiagnostics" class="encoderParameterDiagnostics"></div>
            </li>
```

- [ ] **Step 4: Emit the templates JSON dump**

At the top of `monitor.php`, near the existing `require_once` statements, add:

```php
require_once('includes/EncoderTemplates.php');
```

Then within the form rendering — anywhere before the closing `</form>` and before the `<script>` tag from Step 5 — emit:

```php
<script>window.ZM_ENCODER_TEMPLATES = <?php echo json_encode(ZM\EncoderTemplates::all(), JSON_UNESCAPED_SLASHES); ?>;</script>
```

(Place it adjacent to the other inline `<script>` blocks in monitor.php so it's easy to find. A good site is just before the existing `<script src="...MonitorLinkExpression.js"></script>` near line 1705.)

- [ ] **Step 5: Add the JS module `<script>` tag**

Adjacent to the existing `<script src="<?php echo cache_bust('js/MonitorLinkExpression.js') ?>"></script>` line (~1705), add:

```php
<script src="<?php echo cache_bust('skins/classic/views/js/monitor-encoder-presets.js') ?>"></script>
```

- [ ] **Step 6: PHP-lint**

Run: `php -l web/skins/classic/views/monitor.php`
Expected: `No syntax errors detected`.

- [ ] **Step 7: Commit**

```bash
git add web/skins/classic/views/monitor.php
git commit -m "$(cat <<'EOF'
feat: render encoder Preset row and emit templates JSON refs #<N>

Adds a new Preset row to the monitor edit form between the Encoder
dropdown and EncoderParameters textarea, including:
- a Preset <select> populated client-side from window.ZM_ENCODER_TEMPLATES
- an Apply button that triggers the merge in monitor-encoder-presets.js
- a diagnostics <div> below the textarea for advisory lint output
- ids ZmEncoder / EncoderParameters / EncoderPresetRow so the JS can
  find them with getElementById

Includes the templates dict via a server-side json_encode of
ZM\EncoderTemplates::all() into a single <script> block, and loads the
new JS module via cache_bust(). The __preset_picker form field is
intentionally not a Monitor column and is silently dropped on save.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Add CSS and translation strings

**Files:**
- Modify: `web/skins/classic/css/base/views/monitor.css`
- Modify: `web/skins/classic/css/dark/views/monitor.css` (if it exists)
- Modify: `web/skins/classic/css/light/views/monitor.css` (if it exists)
- Modify: `web/lang/en_gb.php`

- [ ] **Step 1: Find which monitor.css files exist**

Run: `ls web/skins/classic/css/*/views/monitor.css 2>/dev/null`

- [ ] **Step 2: Append the diagnostics style to each monitor.css**

For each file from Step 1 (typically `base`, `dark`, `light` skin variants), append:

```css

/* Encoder preset diagnostics — advisory text under the EncoderParameters textarea */
.encoderParameterDiagnostics {
  font-size: 0.9em;
  color: #b58900;
  margin-top: 4px;
}
```

Use the colour as-is — ZM's CSS does not currently expose a `--color-warning` variable; the literal value matches the existing solarized-leaning palette used on other warning text.

- [ ] **Step 3: Add translation keys**

Open `web/lang/en_gb.php`. The file is an associative array of keys to translated strings. Find an alphabetically appropriate location and add:

```php
  'EncoderPreset' => 'Encoder Preset',
  'ApplyEncoderPreset' => 'Apply preset',
```

(The cross-encoder dialog and lint diagnostic strings are constructed in JS rather than PHP — they don't need translation keys for v1; once the surrounding translation infra is settled, they can be moved to the lang file in a follow-up.)

- [ ] **Step 4: Verify and commit**

Run: `php -l web/lang/en_gb.php`
Expected: `No syntax errors detected`.

```bash
git add web/skins/classic/css/*/views/monitor.css web/lang/en_gb.php
git commit -m "$(cat <<'EOF'
feat: add encoder-preset CSS and translation strings refs #<N>

- .encoderParameterDiagnostics: small warning-coloured advisory style
  for the lint output beneath the EncoderParameters textarea, applied
  consistently across base/dark/light skin variants.
- en_gb.php: EncoderPreset and ApplyEncoderPreset translation keys.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: End-to-end verification on pseudo

**Files:**
- (none — manual browser testing)

- [ ] **Step 1: Deploy to pseudo**

Per the project's pseudo-deploy convention (root-owned `/usr/share/zoneminder/www`), the user will need to copy the new and modified files. The plan reaches a verifiable artifact at this point; ask the user to deploy these files to pseudo:

```
web/includes/EncoderTemplates.php
web/skins/classic/views/monitor.php
web/skins/classic/views/js/monitor-encoder-presets.js
web/skins/classic/css/base/views/monitor.css
web/skins/classic/css/dark/views/monitor.css   (if it exists)
web/skins/classic/css/light/views/monitor.css  (if it exists)
web/lang/en_gb.php
```

- [ ] **Step 2: Open Chrome to a monitor edit page**

Navigate to https://pseudo.connortechnology.com/?view=monitor&mid=<some-monitor> in the existing Chrome session. Confirm the page renders without JS errors.

- [ ] **Step 3: Walk through the verification checklist**

Confirm each of the spec's manual test cases:

  1. Set Encoder to `libx264`. Preset row appears with `Balanced / Archival / Low CPU` options. Click Apply with empty textarea — textarea fills with the preset's serialized form.
  2. Click Apply again — textarea contents unchanged (idempotent).
  3. Add a hand-typed line `custom_x=1`. Click Apply on a preset — `custom_x=1` survives, template keys overwrite the rest.
  4. Type `bogus_key=42` in the textarea. The diagnostics div below the textarea displays "Note: `bogus_key` is not recognised…". Remove the line — diagnostics clear.
  5. Apply Archival on libx264. Switch Encoder to `hevc_nvenc`. The preset dropdown repopulates with hevc_nvenc presets. No same-kind match (hevc_nvenc has no `archival` preset by default) — no dialog. Switch encoder to `libx265` instead — same-kind match exists, bootbox confirm appears. Click Apply — textarea merges in libx265 Archival. Click Cancel on a subsequent encoder switch — no merge happens.
  6. Switch Encoder to `libsvtav1` (no template entry). Preset row hides. Diagnostics clear. Textarea remains untouched.
  7. Save the monitor. Reload the edit page. EncoderParameters value matches what was saved. The `__preset_picker` value did not persist (verify in DB or just by reloading and seeing no spurious column written — the field has no DB column so it's safe by construction, but worth confirming the page still loads).

- [ ] **Step 4: Capture and report any issues**

If any step fails, return to the affected task and add a bugfix step, including a regression test in `tests/js/encoder-presets.test.js` if it's a pure-function bug.

- [ ] **Step 5: Once verification passes, no commit needed**

Document any minor adjustments (e.g. translation strings, colour tweak) in a follow-up small commit; the feature itself is feature-complete at this point.

---

## Task 12: Merge to master

**Files:**
- (git only)

- [ ] **Step 1: Confirm clean state on the feature branch**

```bash
git status
git log --oneline master..HEAD
```

Expected: working tree clean, ~7 commits on the branch (spec + 6 feat/feat-CSS/lang).

- [ ] **Step 2: Run the full lint and test suites**

```bash
node tests/js/encoder-presets.test.js
npx eslint .
php -l web/includes/EncoderTemplates.php
php -l web/skins/classic/views/monitor.php
php -l web/lang/en_gb.php
```

All four expected to be clean / exit 0.

- [ ] **Step 3: Merge with --no-ff and push**

```bash
git checkout master
git merge --no-ff <N>-encoder-presets -m "Merge branch '<N>-encoder-presets'"
git push origin master
git branch -d <N>-encoder-presets
```

- [ ] **Step 4: Verify the issue auto-closes**

After the push reaches the connortechnology fork, the `fixes #<N>` reference in any merge commit message will close the issue. (We used `refs #<N>` in feature commits and not `fixes #<N>` — adjust the merge commit message to include `fixes #<N>` if you want auto-close.)

```bash
gh issue view <N>
```

Expected: state CLOSED, or still OPEN — close it manually with `gh issue close <N>` if needed.

---

## Self-review notes

- **Spec coverage:** Every section of the spec maps to a task: data model → Task 2; UI → Task 9; JS module pure functions → Tasks 3-6 (with tests); JS DOM wiring → Task 7; CSS + i18n → Task 10; manual verification → Task 11; out-of-scope items (DB, ffmpeg introspection, round-trip detection, custom presets) are explicitly not implemented, matching the spec.
- **Type/name consistency:** The function names (`parseParams`, `mergeParams`, `serializeParams`, `lint`, `init`, `applyPreset`, `repopulatePresets`, `runLint`, `onEncoderChange`) are stable across all tasks. DOM ids `ZmEncoder`, `EncoderPreset`, `EncoderPresetRow`, `ApplyEncoderPreset`, `EncoderParameters`, `EncoderParameterDiagnostics` are referenced consistently.
- **Tests live in code:** Each test in `tests/js/encoder-presets.test.js` shows actual `assert.deepStrictEqual(...)` lines, not "write tests for this".
- **Frequent commits:** 6 feature commits + 1 spec commit = 7 commits across the branch, each independently meaningful.
- **No placeholders:** No TBD/TODO/etc. in tasks.
