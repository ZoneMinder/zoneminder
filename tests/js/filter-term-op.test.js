'use strict';

// Operator validation for filter terms in web/skins/classic/views/js/filter.js.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/filter.js');
const src = fs.readFileSync(srcPath, 'utf8');

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

const ctx = {
  console,
  document: {},
  alert() {},
  confirm: () => true,
  $j: () => ({ready() {}}),
  opTypes: {'=': 'eq', '!=': 'ne', '>=': 'ge', 'IS': 'is', 'IS NOT': 'isnot', 'LIKE': 'like'},
  tags_opTypes: {'=': 'eq', '!=': 'ne'},
};
vm.createContext(ctx);
vm.runInContext(src, ctx);

test('termOpValue ExistsInFileSystem = defaults to IS', () => {
  assert.strictEqual(ctx.termOpValue('ExistsInFileSystem', '='), 'IS');
});
test('termOpValue ExistsInFileSystem IS NOT kept', () => {
  assert.strictEqual(ctx.termOpValue('ExistsInFileSystem', 'IS NOT'), 'IS NOT');
});
test('termOpValue ExistsInFileSystem undefined defaults to IS', () => {
  assert.strictEqual(ctx.termOpValue('ExistsInFileSystem', undefined), 'IS');
});
test('termOpValue Tags >= falls back to =', () => {
  assert.strictEqual(ctx.termOpValue('Tags', '>='), '=');
});
test('termOpValue Tags != kept', () => {
  assert.strictEqual(ctx.termOpValue('Tags', '!='), '!=');
});
test('termOpValue Name LIKE kept', () => {
  assert.strictEqual(ctx.termOpValue('Name', 'LIKE'), 'LIKE');
});
test('termOpValue Name bogus falls back to =', () => {
  assert.strictEqual(ctx.termOpValue('Name', 'bogus'), '=');
});
test('termOpValue Name undefined defaults to =', () => {
  assert.strictEqual(ctx.termOpValue('Name', undefined), '=');
});

console.log(passed + ' passed, ' + failed + ' failed');
if (failed) process.exit(1);
