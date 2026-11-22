'use strict';

// Switching frames quickly starts two image loads. Whichever finished last
// was painted, so frame 5's image could replace frame 6's, and its size went
// into imageNaturalW/H, which save() sends with frame 6's boxes.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TRAINING_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');
const match = src.match(
    /AnnotationEditor\.prototype\._loadFrameImage = function\(frameId\) \{[\s\S]*?\n\};\n/);
assert.ok(match, '_loadFrameImage not found in training.js');

const images = [];
function Image() {
  images.push(this);
}
const sandbox = {
  Image, AnnotationEditor: function() {}, thisUrl: '?',
  document: {createElement: () => ({})},
};
vm.createContext(sandbox);
vm.runInContext(match[0], sandbox);

const style = {};
const editor = Object.create(sandbox.AnnotationEditor.prototype);
Object.assign(editor, {
  eventId: 1, translations: {}, totalFrames: 100,
  canvas: {style, parentNode: null, width: 0, height: 0},
  _updateFrameInfo() {}, _render() {}, _setStatus() {}, switchFrame() {},
});

editor._loadFrameImage('5');
editor._loadFrameImage('6');
assert.strictEqual(images.length, 2);
// Frame 6 arrives first, then the late frame 5.
Object.assign(images[1], {naturalWidth: 640, naturalHeight: 480});
images[1].onload();
Object.assign(images[0], {naturalWidth: 1920, naturalHeight: 1080});
images[0].onload();

assert.strictEqual(editor.currentFrameId, '6');
assert.strictEqual(editor.image, images[1], 'the current frame keeps its image');
assert.strictEqual(editor.imageNaturalW, 640, 'and its size');
assert.strictEqual(editor.canvas.width, 640, 'and the canvas size');
console.log('ok - a late frame image does not replace the current one');
