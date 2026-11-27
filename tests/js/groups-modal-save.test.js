'use strict';

// The group modal switched Save off when the name was emptied and never back
// on, so retyping a name left it dead. The New button also inserted the modal
// under a misspelt id.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../../web');
const src = fs.readFileSync(path.join(root, 'skins/classic/views/js/groups.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function load(form) {
  const globals = {
    canEdit: {Groups: true},
    console: {log() {}, error() {}},
    document: {getElementById: (id) => (id === 'groupForm' ? form : null)},
  };
  const sandbox = vm.createContext(globals);
  vm.runInContext(src, sandbox);
  return sandbox;
}

test('Save follows the name field in both directions', () => {
  const form = {elements: {'action': {disabled: false}, 'newGroup[Name]': {value: ''}}};
  const ctx = load(form);
  ctx.configModalBtns();
  assert.strictEqual(form.elements['action'].disabled, true);
  form.elements['newGroup[Name]'].value = 'Front';
  ctx.configModalBtns();
  assert.strictEqual(form.elements['action'].disabled, false);
});

test('the new group modal is inserted under its own id', () => {
  assert.ok(!src.includes('groupdModal'));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
