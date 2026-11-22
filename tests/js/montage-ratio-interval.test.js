'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const montagePath = process.argv[2] || process.env.MONTAGE_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/montage.js');
const src = fs.readFileSync(montagePath, 'utf8');

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

function loadMontage() {
  const state = {created: [], cleared: [], timers: []};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: makeChainable(),
    ResizeObserver: class {
      observe() {}
    },
    document: {
      visibilityState: 'visible',
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener() {},
    },
    innerWidth: 1000,
    innerHeight: 800,
    ZM_WEB_NAVBAR_STICKY: '0',
    ZM_WEB_VIEWING_TIMEOUT: 0,
    ZM_PRESET_LAYOUT_NAMES: ['Auto', '1 Wide'],
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    whenAuthFresh: (cb) => cb(),
    setInterval: (fn, ms) => {
      const handle = state.created.length + 1;
      state.created.push({handle, fn, ms});
      return handle;
    },
    clearInterval: (handle) => {
      // Browsers treat clearInterval(null) as a no-op.
      if (handle !== null && handle !== undefined) state.cleared.push(handle);
    },
    setTimeout: (fn, ms) => {
      state.timers.push({fn, ms});
      return state.timers.length;
    },
    clearTimeout() {},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

function liveHandles(state) {
  return state.created.map((i) => i.handle)
      .filter((h) => !state.cleared.includes(h));
}

function latestInterval(state) {
  return state.created[state.created.length - 1];
}

test('calling waitingMonitorsPlaced twice leaves only one interval live', () => {
  const {sandbox, state} = loadMontage();
  sandbox.checkEndMonitorsPlaced = () => false;
  sandbox.waitingMonitorsPlaced('changeRatio');
  assert.strictEqual(liveHandles(state).length, 1);
  sandbox.waitingMonitorsPlaced('changeRatio');
  assert.strictEqual(state.created.length, 2);
  assert.deepStrictEqual(liveHandles(state), [2]);
});

test('a poll that never succeeds is cleared after the attempt limit', () => {
  const {sandbox, state} = loadMontage();
  sandbox.checkEndMonitorsPlaced = () => false;
  sandbox.waitingMonitorsPlaced(null);
  const interval = latestInterval(state);
  assert.strictEqual(interval.ms, 100);
  for (let i = 0; i < 99; i++) interval.fn();
  assert.deepStrictEqual(liveHandles(state), [1]);
  interval.fn();
  assert.deepStrictEqual(state.cleared, [1]);
  assert.deepStrictEqual(liveHandles(state), []);
});

test('a poll that succeeds clears its interval on the first tick', () => {
  const {sandbox, state} = loadMontage();
  sandbox.checkEndMonitorsPlaced = () => true;
  sandbox.waitingMonitorsPlaced(null);
  latestInterval(state).fn();
  assert.deepStrictEqual(state.cleared, [1]);
  assert.deepStrictEqual(liveHandles(state), []);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
