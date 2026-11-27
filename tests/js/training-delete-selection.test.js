'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');

const sandbox = {
  $j: () => ({on: () => {}, ready: () => {}}),
  console: console,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
const AnnotationEditor = vm.runInContext('AnnotationEditor', sandbox);

function run(selected, del) {
  const ed = Object.create(AnnotationEditor.prototype);
  ed.annotations = [{label: 'a'}, {label: 'b'}, {label: 'c'}];
  ed.selectedIndex = selected;
  ed.undoStack = [];
  ed.currentFrameId = '1';
  ed._updateSidebar = () => {};
  ed._render = () => {};
  const before = selected >= 0 ? ed.annotations[selected] : null;
  ed.deleteAnnotation(del);
  return {ed: ed, before: before};
}

let r = run(2, 0);
assert.strictEqual(r.ed.annotations[r.ed.selectedIndex], r.before,
    'deleting box 0 must keep the last box selected');
r = run(1, 1);
assert.strictEqual(r.ed.selectedIndex, -1, 'deleting the selected box deselects');
r = run(2, 1);
assert.strictEqual(r.ed.annotations[r.ed.selectedIndex], r.before,
    'deleting the middle box must keep the last box selected');
r = run(0, 2);
assert.strictEqual(r.ed.annotations[r.ed.selectedIndex], r.before,
    'deleting a later box keeps the selection');
console.log('ok - delete keeps the same box selected');
