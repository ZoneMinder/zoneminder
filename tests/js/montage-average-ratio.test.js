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

function averageRatioFor(arr) {
  const {sandbox} = loadMontage();
  sandbox.calculateAverageMonitorsRatio(arr);
  return vm.runInContext('averageMonitorsRatio', sandbox);
}

test('all monitors valid 16:9 gives 1.778', () => {
  const result = averageRatioFor([1.778, 1.778]);
  assert.strictEqual(Number(result), 1.778);
});

test('a monitor with width 0 and height 0 (NaN ratio) is ignored', () => {
  const result = averageRatioFor([1.778, NaN]);
  assert.strictEqual(Number(result), 1.778);
  assert.notStrictEqual(result, '');
});

test('an Infinity ratio is ignored', () => {
  const result = averageRatioFor([1.333, Infinity]);
  assert.strictEqual(Number(result), 1.333);
});

test('empty array falls back to a positive default ratio', () => {
  const result = averageRatioFor([]);
  assert.notStrictEqual(result, '');
  assert.ok(Number(result) > 0);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
