'use strict';

// Wheel zoom must follow the modifiers on the wheel event itself. A keydown
// whose keyup never arrives (focus lost) must not make plain wheels zoom.
// Usage: node panzoom-stale-modifier.test.js [path/to/panzoom.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = process.argv[2] ||
    path.join(__dirname, '../../web/js/panzoom.js');
const src = fs.readFileSync(jsPath, 'utf8');

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

function makeElement(id) {
  const listeners = {};
  return {
    id,
    listeners,
    style: {},
    parentElement: null,
    addEventListener(type, fn) {
      (listeners[type] = listeners[type] || new Set()).add(fn);
    },
    removeEventListener(type, fn) {
      if (listeners[type]) listeners[type].delete(fn);
    },
  };
}

function load() {
  const parent = makeElement('parent');
  const obj = makeElement('liveStream5');
  obj.parentElement = parent;
  const state = {wheelZooms: 0};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}},
    panZoomEnabled: true,
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    Panzoom: () => ({
      getScale() {
        return 1;
      },
      zoomWithWheel() {
        state.wheelZooms++;
      },
      reset() {},
      resetStyle() {},
      setOptions() {},
      destroy() {},
    }),
    $j: () => ({
      off: () => ({on() {}}),
      addClass() {},
      removeClass() {},
      find: () => ({addClass() {}}),
    }),
    document: {getElementById: () => null},
  };
  vm.createContext(sandbox);
  vm.runInContext(src + ';this.zmPanZoom = zmPanZoom;', sandbox);
  sandbox.zmPanZoom.getStream = () => [obj];
  sandbox.zmPanZoom.action('enable', {obj});
  return {zm: sandbox.zmPanZoom, parent, state};
}

function wheel(parent, props) {
  const ev = Object.assign({
    shiftKey: false, altKey: false, ctrlKey: false, deltaY: -1,
    preventDefault() {},
  }, props);
  for (const fn of parent.listeners.wheel) fn(ev);
}

test('plain wheel after Shift keydown without keyup is not a zoom', () => {
  const {zm, parent, state} = load();
  zm.shifted = true; // keydown seen, keyup lost
  // window lost focus: no keyup is delivered
  wheel(parent, {});
  assert.strictEqual(state.wheelZooms, 0);
});

test('plain wheel after Alt keydown without keyup is not a zoom', () => {
  const {zm, parent, state} = load();
  zm.alted = true; // keydown seen, keyup lost
  wheel(parent, {});
  assert.strictEqual(state.wheelZooms, 0);
});

test('wheel with shiftKey set zooms', () => {
  const {parent, state} = load();
  wheel(parent, {shiftKey: true});
  assert.strictEqual(state.wheelZooms, 1);
});

test('wheel with shiftKey zooms even if tracked state is stale', () => {
  const {zm, parent, state} = load();
  zm.shifted = false;
  wheel(parent, {shiftKey: true});
  assert.strictEqual(state.wheelZooms, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
