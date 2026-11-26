'use strict';

// Changing frame in the training editor never loaded the boxes already saved
// for that frame, so a saved frame opened empty and saving it again wrote over
// its labels.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TRAINING_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');
function method(name) {
  const m = src.match(new RegExp('AnnotationEditor\\.prototype\\.' + name +
      ' = function\\([^)]*\\) \\{[\\s\\S]*?\\n\\};\\n'));
  assert.ok(m, name + ' not found in training.js');
  return m[0];
}

const requests = [];
const $j = () => ({});
$j.getJSON = (url) => {
  const req = {url, done: null};
  requests.push(req);
  const p = {
    done(cb) {
      req.done = cb; return p;
    },
    fail() {
      return p;
    },
  };
  return p;
};
const sandbox = {$j, thisUrl: '?', logAjaxFail() {}, AnnotationEditor: function() {}};
vm.createContext(sandbox);
vm.runInContext(method('switchFrame') + method('_loadSavedAnnotations'), sandbox);

const editor = Object.create(sandbox.AnnotationEditor.prototype);
Object.assign(editor, {
  eventId: 1, currentFrameId: '4', annotations: [], dirty: false, translations: {},
  _confirmDiscardIfDirty: () => true,
  _resetAnnotationState() {
    this.annotations = []; this.dirty = false;
  },
  _loadFrameImage(fid) {
    this.currentFrameId = fid;
  },
  _updateSidebar() {}, _render() {}, _setStatus() {},
});
const saved = {result: 'Ok', response: {annotations: [{x1: 1, y1: 2, x2: 3, y2: 4, label: 'person'}]}};

editor.switchFrame('5');
const load = requests.find((r) => /action=load_saved&eid=1&fid=5$/.test(r.url));
assert.ok(load, 'switching frame asks for the frame\'s saved annotations');
load.done(saved);
assert.strictEqual(editor.annotations.length, 1, 'and shows them');
assert.strictEqual(editor.dirty, false, 'without marking the frame dirty');

editor.switchFrame('6');
const late = requests[requests.length - 1];
editor.switchFrame('7');
late.done(saved);
assert.strictEqual(editor.annotations.length, 0, 'a late reply for another frame is ignored');
console.log('ok - switching frame loads that frame\'s saved annotations');
