'use strict';

// getCmdResponse re-sends CMD_SCALE when the scale zms reports differs from
// the one the page wants. streamScale() never asks zms for more than 100%,
// but the check compared zms's answer with the unclamped currentScale +
// deltaScale(). Whenever that is over 100 (Actual with a positive stream
// quality, or Auto/Fit on a screen wider than the recording) the two never
// matched, so every status reply sent another CMD_SCALE.

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

function load(streamQuality) {
  const sent = [];
  const $j = function(selector) {
    if (selector === '#streamQuality') return {val: () => streamQuality};
    return makeChainable();
  };
  $j.getJSON = function(url, data) {
    sent.push(data);
    const p = {done: () => p, fail: () => p, always: () => p};
    return p;
  };
  const globals = {
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: (id) => (id === 'evtStream' ? {} : null),
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    setButtonState: () => {},
    secsToTime: (s) => String(s),
    checkStreamForErrors: () => false,
    isLightboxOpen: () => false,
    zmPanZoom: {panZoom: {}},
    zmAuth: {hash: '', appendTo: (u) => u, update: () => false},
    monitorUrl: 'index.php',
    connKey: '123456',
    CMD_SCALE: 15,
    eventData: {Id: 10, MonitorId: '1', Width: 640, Height: 480, Length: '10', Frames: '100',
      StartDateTime: '2026-11-23 10:00:00'},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  vm.runInContext('zmsHints = {needsLookup: () => false, take: () => []};', sandbox);
  return {globals, sent};
}

// A status reply from zms playing event 10 at the given scale.
function reply(g, zmsScale) {
  g.getCmdResponse({result: 'Ok', status: {event: 10, scale: zmsScale, progress: 1, rate: 1,
    paused: false, fps: 10}});
}

const scaleCommands = (sent) => sent.filter((d) => d.command === 15).map((d) => d.scale);

test('Auto on a screen wider than the recording does not resend the scale', () => {
  const {globals, sent} = load('0');
  globals.currentScale = 200;
  reply(globals, 100);
  reply(globals, 100);
  assert.deepStrictEqual(scaleCommands(sent), []);
});

test('Actual with a higher stream quality does not resend the scale', () => {
  const {globals, sent} = load('+20');
  globals.currentScale = 100;
  reply(globals, 100);
  assert.deepStrictEqual(scaleCommands(sent), []);
});

test('a scale zms did not apply is still sent again', () => {
  const {globals, sent} = load('0');
  globals.currentScale = 50;
  reply(globals, 100);
  assert.deepStrictEqual(scaleCommands(sent), [50]);
});

test('a lower stream quality is included in the scale compared and sent', () => {
  const {globals, sent} = load('-20');
  globals.currentScale = 50;
  reply(globals, 40);
  assert.deepStrictEqual(scaleCommands(sent), []);
  reply(globals, 50);
  assert.deepStrictEqual(scaleCommands(sent), [40]);
});

test('streamScale still never asks zms for more than 100%', () => {
  const {globals, sent} = load('+50');
  globals.streamScale(200);
  assert.deepStrictEqual(scaleCommands(sent), [100]);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
