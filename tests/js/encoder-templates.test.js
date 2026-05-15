'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname,
    '../../web/skins/classic/views/js/monitor-encoder-templates.js'));

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
test('all-separator input returns empty array', () => {
  assert.deepStrictEqual(ZM.parseParams(',,,'), []);
});

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
test('handles prototype-named keys without crashing', () => {
  assert.deepStrictEqual(
      ZM.mergeParams([], {constructor: 'x', toString: 'y'}),
      [{key: 'constructor', value: 'x'}, {key: 'toString', value: 'y'}]);
});
test('coerces numeric template values to strings', () => {
  assert.deepStrictEqual(
      ZM.mergeParams([], {crf: 23}),
      [{key: 'crf', value: '23'}]);
});

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

console.log('\nlint');
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

console.log('\nfindTemplateByName (cross-encoder match)');
const NAME_FIXTURE = {
  libx264: {
    valid_keys: ['preset'],
    templates: [
      {id: 1, name: 'Balanced', description: '', params: {preset: 'fast'}},
      {id: 2, name: 'Archival', description: '', params: {preset: 'slow'}},
    ],
  },
  libx265: {
    valid_keys: ['preset'],
    templates: [
      {id: 3, name: 'Balanced', description: '', params: {preset: 'fast'}},
      {id: 4, name: 'archival', description: '', params: {preset: 'slow'}},
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

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
