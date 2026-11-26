'use strict';

// Deleting the last box on a frame removes the frame from the training set
// with action=delete. A refused delete (no Events edit permission, bad frame)
// comes back as HTTP 200 with result 'Error', and the editor still said
// "Training annotation removed".

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TRAINING_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');
const match = src.match(
    /AnnotationEditor\.prototype\.deleteAnnotation = function\(index\) \{[\s\S]*?\n\};\n/);
assert.ok(match, 'deleteAnnotation not found in training.js');

function run(reply) {
  const statuses = [];
  const $j = () => ({});
  $j.ajax = () => {
    const p = {
      done(cb) {
        cb(reply); return p;
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
  const editor = Object.create(sandbox.AnnotationEditor.prototype);
  Object.assign(editor, {
    annotations: [{label: 'person'}], selectedIndex: 0, currentFrameId: '5', eventId: 1,
    translations: {}, _browseState: null,
    _pushUndo() {}, _updateSidebar() {}, _render() {}, _renderStats() {},
    _setStatus: (msg, type) => statuses.push([msg, type]),
  });
  editor.deleteAnnotation(0);
  return statuses;
}

let s = run({result: 'Error', message: 'Permission denied'});
assert.deepStrictEqual(s, [['Permission denied', 'error']], 'a refused delete is reported as an error');
s = run({result: 'Ok', response: {}});
assert.strictEqual(s.length, 1);
assert.strictEqual(s[0][1], 'success', 'a real delete still reports success');
console.log('ok - training delete reports a refused delete');
