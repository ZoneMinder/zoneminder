'use strict';

// img_onerror() schedules an auth refresh and reconnect a few seconds out.
// stop() did not cancel it, so pressing Stop (or switching monitor) during that
// window let the timer fire and point the img at a new zms connkey.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');

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

function load() {
  const timers = new Map();
  let next = 1;
  const jq = makeChainable();
  const globals = {
    Object, Math, Array, JSON, Set, Map, Date, parseInt, parseFloat, String,
    Number, Promise,
    $j: new Proxy(function() {}, {
      get: (t, k) => (k === 'getJSON' ?
        () => {
          const p = {done: (fn) => {
            fn({}); return p;
          }, fail: () => p};
          return p;
        } : (k === 'then' ? undefined : jq)),
      apply: () => jq,
    }),
    jQuery: {ajaxQueue: () => ({done: () => ({fail: () => {}}), fail: () => {}})},
    zmAuth: {appendTo: (u) => u, applyTo: (u, c) => u + '&connkey=' + c, update() {}},
    manageEventListener: {addEventListener: () => 1, removeEventListener: () => null},
    console: {log() {}, warn() {}, error() {}, debug() {}},
    dateTimeToISOLocal: () => '',
    hideAudioMotion() {},
    thisUrl: 'index.php',
    currentView: 'watch',
    CMD_QUERY: 99, CMD_QUIT: 17, CMD_STOP: 3,
    getCookie: () => 'true',
    statusRefreshTimeout: 1000,
    WebSocket: {CLOSED: 3, CLOSING: 2},
    document: {getElementById: () => null, createElement: () => ({})},
    setTimeout: (fn) => {
      const id = next++; timers.set(id, fn); return id;
    },
    clearTimeout: (id) => {
      timers.delete(id);
    },
    setInterval: () => next++,
    clearInterval: () => {},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  return {globals, timers};
}

console.log('MonitorStream stop() and a pending reconnect');

test('stop() cancels the reconnect img_onerror scheduled', () => {
  const {globals, timers} = load();
  const m = new globals.MonitorStream({
    id: 5, name: 't', connKey: '000111', url: 'index.php',
    url_to_zms: '/zms?monitor=5', width: 640, height: 480,
  });
  const img = {nodeName: 'IMG', src: '/zms?monitor=5&connkey=000111', pause() {}};
  m.getElement = () => img;
  m.writeTextInfoBlock = () => {};
  m.player = 'zms';
  m.activePlayer = 'zms';
  m.started = true;
  m.img_onerror();
  assert.strictEqual(timers.size, 1, 'reconnect should be pending');
  m.isActive = false;
  m.stop({skipStreamCommand: true});
  for (const fn of Array.from(timers.values())) fn();
  assert.strictEqual(img.src, '', 'stopped stream was pointed at a new zms');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
