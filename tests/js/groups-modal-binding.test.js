'use strict';

// The group modal's Save button should start disabled for a new group and follow
// the Name field, but neither happened: the template's ternary had no echo, so
// "disabled" was never written, and the name/parent/monitor handlers are
// data-on-* attributes that skin.js binds at page load, before the modal is
// fetched. A group could be saved with no name. newGroup() and editGroup() now
// bind them after inserting the modal.
//
// Run: node tests/js/groups-modal-binding.test.js [web root]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = process.argv[2] || path.join(__dirname, '../../web');
const src = fs.readFileSync(path.join(root, 'skins/classic/views/js/groups.js'), 'utf8');
const modal = fs.readFileSync(path.join(root, 'ajax/modals/group.php'), 'utf8');

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

function load() {
  const calls = [];
  const chain = {modal: () => chain, chosen: () => chain, remove: () => chain};
  const $j = () => chain;
  $j.getJSON = () => ({
    done(fn) {
      fn({html: '<div id="groupModal"></div>'});
      return {fail() {}};
    },
  });
  const sandbox = vm.createContext({
    $j, thisUrl: '', canEdit: {Groups: true},
    console: {log() {}, error() {}},
    logAjaxFail() {},
    insertModalHtml: () => calls.push('insert'),
    dataOnChange: () => calls.push('change'),
    dataOnInput: () => calls.push('input'),
  });
  vm.runInContext(src, sandbox);
  return {ctx: sandbox, calls};
}

test('the new group modal binds its handlers after it is inserted', () => {
  const {ctx, calls} = load();
  ctx.newGroup();
  const at = calls.indexOf('insert');
  assert.ok(at >= 0, 'modal inserted');
  assert.ok(calls.indexOf('change') > at, 'data-on-change bound after insert: ' + calls);
  assert.ok(calls.indexOf('input') > at, 'data-on-input bound after insert: ' + calls);
});

test('the edit group modal binds its handlers after it is inserted', () => {
  const {ctx, calls} = load();
  ctx.editGroup({getAttribute: () => '7'});
  const at = calls.indexOf('insert');
  assert.ok(calls.indexOf('change') > at && calls.indexOf('input') > at, 'bound after insert: ' + calls);
});

test('the template writes the disabled attribute for a new group', () => {
  assert.ok(/<\?php\s+echo\s+\$newGroup->Id\(\)\s*\?\s*''\s*:\s*' disabled="disabled"'/.test(modal),
      'Save button disabled attribute is echoed');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
