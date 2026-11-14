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

function loadMontage() {
  const calls = {init: 0, enable: 0};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: makeChainable(),
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener() {},
    },
    panZoomEnabled: true,
    zmPanZoom: {
      init() {
        calls.init++;
      },
      action(name) {
        if (name === 'enable') calls.enable++;
      },
    },
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    objGridStack: {disable() {}},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, calls};
}

test('cancel_layout re-enables zoom and pan through zmPanZoom.init()', () => {
  const {sandbox, calls} = loadMontage();
  sandbox.cancel_layout({});
  assert.strictEqual(calls.init, 1);
  assert.strictEqual(calls.enable, 0);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
