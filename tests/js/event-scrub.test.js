'use strict';

// Dragging on the event progress bar scrubs the stream (#4851). Before, only a
// click seeked. These tests cover the position maths, the throttle and the
// wiring that sends CMD_SEEK / video.js currentTime while the pointer moves.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');

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

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

// A $j that records handlers bound to #progressBar and document, and reports a
// bar 200px wide starting at x=100.
function load() {
  const handlers = {bar: {}, doc: {}};
  const docObj = {on: function(ev, fn) {
    ev.split(' ').forEach((e) => handlers.doc[e] = fn); return docObj;
  }};
  const barObj = makeChainable();
  const bar = {
    offset: () => ({left: 100}),
    width: () => 200,
    on: function(ev, fn) {
      ev.split(' ').forEach((e) => handlers.bar[e] = fn); return bar;
    },
    click: () => bar, mouseover: () => bar, mouseout: () => bar, mousemove: () => bar,
  };
  const seeks = [];
  const globals = {
    $j: function(sel) {
      if (sel === '#progressBar') return bar;
      if (sel === globals.document) return docObj;
      return barObj;
    },
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {getElementById: () => null, querySelector: () => null,
      querySelectorAll: () => [], addEventListener: () => {}, createElement: () => ({})},
    setTimeout: () => 0, clearTimeout: () => 0, setInterval: () => 0, clearInterval: () => 0,
    getCookie: () => null, setCookie: () => {},
    rates: [0], eventData: {Length: '60', StartDateTime: '2020-01-01 00:00:00'},
    seeks: seeks,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : (p in t ? t[p] : globalThis[p])),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  globals.seeks = seeks;
  vm.runInContext('vid = {currentTime: function(t) { seeks.push(t); }};', sandbox);
  globals.handlers = handlers;
  return globals;
}

const ev = (pageX, button) => ({pageX: pageX, button: button || 0, preventDefault() {}});

test('scrubSeekTime maps pointer position to seconds and clamps', () => {
  const g = load();
  assert.strictEqual(g.scrubSeekTime(200, 100, 200, 60), 30);
  assert.strictEqual(g.scrubSeekTime(0, 100, 200, 60), 0);
  assert.strictEqual(g.scrubSeekTime(900, 100, 200, 60), 60);
  assert.strictEqual(g.scrubSeekTime(150, 100, 0, 60), 0);
});

test('scrubShouldSend throttles drags but not press and release', () => {
  const g = load();
  assert.strictEqual(g.scrubShouldSend(1050, 1000, false), false);
  assert.strictEqual(g.scrubShouldSend(1100, 1000, false), true);
  assert.strictEqual(g.scrubShouldSend(1001, 1000, true), true);
});

test('press, drag and release seek the player to the pointer', () => {
  const g = load();
  g.progressBarNav();
  assert.ok(g.handlers.bar.pointerdown, 'no pointerdown on the progress bar');
  g.handlers.bar.pointerdown(ev(150));
  assert.deepStrictEqual(Array.from(g.seeks), [15]);
  g.handlers.doc.pointermove(ev(250));
  // too soon after the press, throttled
  assert.deepStrictEqual(Array.from(g.seeks), [15]);
  g.handlers.doc.pointerup(ev(280));
  assert.deepStrictEqual(Array.from(g.seeks), [15, 54]);
});

test('pointer movement without a press does not seek', () => {
  const g = load();
  g.progressBarNav();
  g.handlers.doc.pointermove(ev(250));
  g.handlers.doc.pointerup(ev(250));
  assert.deepStrictEqual(Array.from(g.seeks), []);
});

test('movement after release does not seek', () => {
  const g = load();
  g.progressBarNav();
  g.handlers.bar.pointerdown(ev(100));
  g.handlers.doc.pointerup(ev(100));
  const n = g.seeks.length;
  g.handlers.doc.pointermove(ev(300));
  assert.strictEqual(g.seeks.length, n);
});

test('a non-primary button does not start a scrub', () => {
  const g = load();
  g.progressBarNav();
  g.handlers.bar.pointerdown(ev(150, 2));
  assert.deepStrictEqual(Array.from(g.seeks), []);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
