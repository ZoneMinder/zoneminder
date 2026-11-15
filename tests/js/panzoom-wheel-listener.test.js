'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/panzoom.js'), 'utf8');

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
  return {zm: sandbox.zmPanZoom, parent, obj, state};
}

test('re-enabling after disable keeps a single wheel listener', () => {
  const {zm, parent, obj} = load();
  zm.action('enable', {obj});
  zm.action('disable', {id: 5});
  zm.action('enable', {obj});
  assert.strictEqual(parent.listeners.wheel.size, 1);
});

test('one shift+wheel zooms once after several enable cycles', () => {
  const {zm, parent, obj, state} = load();
  for (let i = 0; i < 3; i++) {
    zm.action('enable', {obj});
    zm.action('disable', {id: 5});
  }
  zm.action('enable', {obj});
  zm.shifted = true;
  zm.alted = false;
  for (const fn of parent.listeners.wheel) fn({preventDefault() {}});
  assert.strictEqual(state.wheelZooms, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
