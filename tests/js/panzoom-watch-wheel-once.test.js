'use strict';

// The watch page enables pan/zoom twice per monitor: on .zoompan, and on its
// parent .imageFeed as an "additional" object that handles Shift+Alt+wheel.
// Each puts a wheel listener on its object's parent, so a wheel over the
// stream reaches both, and both answered plain Shift+wheel by zooming the
// stream. One wheel notch zoomed two steps on the watch page and one on
// montage and event.
//
// Run: node tests/js/panzoom-watch-wheel-once.test.js

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

function makeElement(id, parent) {
  const listeners = {};
  return {
    id,
    listeners,
    style: {},
    parentElement: parent || null,
    addEventListener(type, fn) {
      (listeners[type] = listeners[type] || []).push(fn);
    },
    removeEventListener(type, fn) {
      if (listeners[type]) listeners[type] = listeners[type].filter((f) => f !== fn);
    },
  };
}

// Deliver an event to the target and then each ancestor, as a bubbling wheel
// event is.
function dispatchWheel(target, init) {
  const event = Object.assign({
    target,
    defaultPrevented: false,
    preventDefault() {
      this.defaultPrevented = true;
    },
  }, init);
  for (let el = target; el; el = el.parentElement) {
    for (const fn of (el.listeners.wheel || []).slice()) fn(event);
  }
}

// #monitor5 > #imageFeed5 > .zoompan > #liveStream5, as Monitor::getStreamHTML
// draws it.
function loadWatch() {
  const monitor = makeElement('monitor5');
  const imageFeed = makeElement('imageFeed5', monitor);
  const zoompan = makeElement('', imageFeed);
  const stream = makeElement('liveStream5', zoompan);
  const state = {wheelZooms: 0, pointZooms: 0};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}},
    Math,
    panZoomEnabled: true,
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    Panzoom: () => ({
      zoomWithWheel(event) {
        event.preventDefault(); // as @panzoom/panzoom does
        state.wheelZooms++;
      },
      zoomToPoint() {
        state.pointZooms++;
      },
      getScale: () => 1,
      reset() {},
      resetStyle() {},
      setOptions() {},
      destroy() {},
    }),
    $j: Object.assign(() => ({
      off: () => ({on() {}}),
      addClass() {},
      removeClass() {},
      find: () => ({addClass() {}}),
    }), {isFunction: (f) => typeof f === 'function'}),
    document: {getElementById: () => null},
    setTriggerChangedMonitors: () => {},
  };
  vm.createContext(sandbox);
  vm.runInContext(src + ';this.zmPanZoom = zmPanZoom;', sandbox);
  const zm = sandbox.zmPanZoom;
  zm.getStream = () => [stream];
  // watch.js: zmPanZoom.init(); then the .imageFeed init with additional: true.
  zm.action('enable', {obj: zoompan});
  zm.action('enable', {obj: imageFeed, objString: '.imageFeed', disablePan: true,
    contain: 'inside', additional: true});
  return {stream, imageFeed, monitor, state};
}

test('one Shift+wheel notch over the stream zooms once', () => {
  const {stream, state} = loadWatch();
  dispatchWheel(stream, {shiftKey: true, altKey: false, deltaY: -100});
  assert.strictEqual(state.wheelZooms, 1);
});

test('Shift+Alt+wheel still zooms the .imageFeed object', () => {
  const {stream, state} = loadWatch();
  stream.closest = (sel) => (sel === '#monitor5' ? {getBoundingClientRect: () => ({x: 0, y: 0, width: 100})} : null);
  dispatchWheel(stream, {shiftKey: true, altKey: true, deltaY: -100});
  assert.strictEqual(state.pointZooms, 1);
  assert.strictEqual(state.wheelZooms, 0);
});

test('Shift+wheel outside .imageFeed still zooms the stream', () => {
  // e.g. the state line under the stream, inside #monitor5 only
  const {monitor, state} = loadWatch();
  const below = makeElement('', monitor);
  dispatchWheel(below, {shiftKey: true, altKey: false, deltaY: -100});
  assert.strictEqual(state.wheelZooms, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
