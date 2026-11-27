'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');

function makeEditor() {
  const docListeners = {};
  const canvasListeners = {};
  const canvas = {
    width: 800,
    height: 600,
    addEventListener: (t, fn) => {
      canvasListeners[t] = fn;
    },
    getContext: () => ({}),
    getBoundingClientRect: () => ({left: 0, top: 0, width: 800, height: 600}),
  };
  const sandbox = {
    $j: () => ({on: () => {}, hasClass: () => false}),
    console: console,
    document: {
      getElementById: () => canvas,
      addEventListener: (t, fn) => {
        docListeners[t] = fn;
      },
    },
    requestAnimationFrame: (fn) => fn(),
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  const AE = vm.runInContext('AnnotationEditor', sandbox);
  const ed = new AE({canvasId: 'c', eventId: 1});
  ed.init();
  ed._updateSidebar = () => {};
  ed._render = () => {};
  ed._updateCursor = () => {};
  return {ed, docListeners, canvasListeners};
}

const up = (m, e) => (m.canvasListeners.mouseup || m.docListeners.mouseup)(e);
const outside = (m, e) => (m.docListeners.mouseup || (() => {}))(e);
const ev = (x, y) => ({button: 0, clientX: x, clientY: y, shiftKey: false});

// A click on a box without moving must not add an undo entry.
let m = makeEditor();
m.ed.annotations = [{label: 'a', x1: 100, y1: 100, x2: 200, y2: 200}];
m.canvasListeners.mousedown(ev(150, 150));
up(m, ev(150, 150));
assert.strictEqual(m.ed.undoStack.length, 0, 'plain click pushed an undo entry');

// A real drag is undoable with a single entry.
m.canvasListeners.mousedown(ev(150, 150));
m.canvasListeners.mousemove(ev(170, 160));
m.canvasListeners.mousemove(ev(190, 170));
up(m, ev(190, 170));
assert.strictEqual(m.ed.undoStack.length, 1);
assert.strictEqual(m.ed.annotations[0].x1, 140);
m.ed.undo();
assert.strictEqual(m.ed.annotations[0].x1, 100);

// Releasing outside the canvas ends a drawing gesture.
m = makeEditor();
m.canvasListeners.mousedown(ev(50, 50));
assert.ok(m.ed.isDrawing);
m.ed._showLabelPicker = () => {};
outside(m, ev(300, 700));
assert.strictEqual(m.ed.isDrawing, false, 'isDrawing stuck after release outside');

// Releasing outside ends a drag.
m = makeEditor();
m.ed.annotations = [{label: 'a', x1: 100, y1: 100, x2: 200, y2: 200}];
m.canvasListeners.mousedown(ev(150, 150));
m.canvasListeners.mousemove(ev(160, 150));
outside(m, ev(900, 150));
assert.strictEqual(m.ed.isDragging, false, 'isDragging stuck after release outside');
console.log('ok - training mouse gestures');
