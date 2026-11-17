'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/montage.js'), 'utf8');

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
    get: (target, prop) => {
      if (prop === 'then') return undefined;
      if (prop === 'val') return () => '';
      return proxy;
    },
    apply: () => proxy,
  });
  return proxy;
}

// A WebSite monitor is printed by montage.php as a bare <object id="liveStreamN">:
// there is no #imageFeedN wrapper and no #ratioN select.
function loadMontage(elements = {}) {
  const state = {intervals: [], cleared: [], timeouts: []};
  const document = {
    visibilityState: 'visible',
    getElementById: (id) => elements[id] || null,
    querySelectorAll: () => [],
    addEventListener() {},
  };
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: makeChainable(),
    ResizeObserver: class {
      observe() {}
    },
    document,
    innerWidth: 1000,
    innerHeight: 800,
    ZM_WEB_NAVBAR_STICKY: '0',
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    whenAuthFresh: (cb) => cb(),
    ZM_PRESET_LAYOUT_NAMES: ['Auto', '1 Wide'],
    setInterval: (fn, ms, ...args) => {
      state.intervals.push({fn, ms, args});
      return state.intervals.length;
    },
    clearInterval: (t) => state.cleared.push(t),
    setTimeout: (fn, ms) => {
      state.timeouts.push({fn, ms});
      return state.timeouts.length;
    },
    clearTimeout() {},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

function rect(r) {
  return {getBoundingClientRect: () => r};
}

function webSiteMonitor(id, refresh) {
  return {
    id, type: 'WebSite', refresh, started: false, player: null,
    getElement: () => rect({top: 10, bottom: 200, left: 10, right: 300}),
    setPlayer() {}, start() {}, stop() {}, setup_onclick() {},
  };
}

test('setRatioForMonitor tolerates a monitor with no ratio select', () => {
  const {sandbox} = loadMontage();
  vm.runInContext('monitors.push({id: 7, width: 640, height: 480})', sandbox);
  const stream = {id: 'liveStream7', clientWidth: 300, style: {}, parentNode: {style: {}}};
  assert.doesNotThrow(() => sandbox.setRatioForMonitor(stream, 7));
});

test('reloadWebSite reloads a bare website object', () => {
  const attrs = {data: 'http://example.com/'};
  const obj = {
    getAttribute: (n) => attrs[n],
    setAttribute: (n, v) => {
      attrs[n] = v;
      obj.reloads = (obj.reloads || 0) + 1;
    },
  };
  const {sandbox} = loadMontage({liveStream4: obj});
  assert.doesNotThrow(() => sandbox.reloadWebSite(4));
  assert.strictEqual(obj.reloads, 1);
});

test('website refresh timers are cleared when the page is hidden', () => {
  const {sandbox, state} = loadMontage();
  vm.runInContext('monitors.push(webSiteMonitor)', Object.assign(sandbox, {webSiteMonitor: webSiteMonitor(3, 5)}));
  sandbox.startMonitors();
  assert.strictEqual(state.intervals.length, 1);
  sandbox.document.visibilityState = 'hidden';
  sandbox.document.onvisibilitychange();
  state.timeouts[state.timeouts.length - 1].fn();
  assert.deepStrictEqual(state.cleared, [1]);
});

test('website refresh timers restart when the streams are restarted', () => {
  const {sandbox, state} = loadMontage();
  vm.runInContext('monitors.push(webSiteMonitor)', Object.assign(sandbox, {webSiteMonitor: webSiteMonitor(3, 5)}));
  sandbox.startMonitors();
  sandbox.document.visibilityState = 'hidden';
  sandbox.document.onvisibilitychange();
  state.timeouts[state.timeouts.length - 1].fn();
  sandbox.document.visibilityState = 'visible';
  sandbox.document.onvisibilitychange();
  assert.strictEqual(state.intervals.length, 2);
  assert.strictEqual(state.intervals[1].ms, 5000);
});

test('a monitor wider than the viewport that spans it is not out of the viewport', () => {
  const {sandbox} = loadMontage();
  const out = sandbox.isOutOfViewport(rect({top: 100, bottom: 400, left: -50, right: 1500}));
  assert.strictEqual(out.all, false);
});

test('a monitor entirely off either side is out of the viewport', () => {
  const {sandbox} = loadMontage();
  assert.strictEqual(sandbox.isOutOfViewport(rect({top: 100, bottom: 400, left: -500, right: -10})).all, true);
  assert.strictEqual(sandbox.isOutOfViewport(rect({top: 100, bottom: 400, left: 1200, right: 1500})).all, true);
  assert.strictEqual(sandbox.isOutOfViewport(rect({top: 100, bottom: 400, left: 10, right: 300})).all, false);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
