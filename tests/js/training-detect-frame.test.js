'use strict';

// Detection results were applied to whatever frame was showing when the
// reply arrived. Moving to the next frame during a slow detect put the
// previous frame's boxes on the new frame, marked dirty and ready to save.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TRAINING_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');
const match = src.match(/AnnotationEditor\.prototype\.detect = function\(\) \{[\s\S]*?\n\};\n/);
assert.ok(match, 'detect not found in training.js');

let reply = null;
const $j = () => ({prop() {}});
$j.ajax = () => {
  const p = {
    done(cb) {
      reply = cb; return p;
    },
    fail() {
      return p;
    },
  };
  return p;
};
const sandbox = {$j, thisUrl: '?', logAjaxFail() {}, AnnotationEditor: function() {}};
vm.createContext(sandbox);
vm.runInContext(match[0], sandbox);

function editorOn(frame) {
  const e = Object.create(sandbox.AnnotationEditor.prototype);
  Object.assign(e, {
    hasDetectScript: true, currentFrameId: frame, eventId: 1, monitorId: 2,
    annotations: [], dirty: false, translations: {},
    _setStatus() {}, _pushUndo() {}, _updateSidebar() {}, _render() {},
  });
  return e;
}
const found = {result: 'Ok', response: {detections: [{label: 'person', confidence: 0.9, bbox: [1, 2, 3, 4]}]}};

let editor = editorOn('5');
editor.detect();
editor.currentFrameId = '6';
reply(found);
assert.strictEqual(editor.annotations.length, 0, 'frame 5 detections are not put on frame 6');
assert.strictEqual(editor.dirty, false, 'and frame 6 is not marked dirty');

editor = editorOn('5');
editor.detect();
reply(found);
assert.strictEqual(editor.annotations.length, 1, 'detections still land on the frame they were run on');
console.log('ok - detect results stay with their frame');
