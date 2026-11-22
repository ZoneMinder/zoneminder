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

function loadMontage() {
  const state = {select: '12'};
  const noop = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : noop),
    apply: () => noop,
  });
  const jq = (sel) => {
    if (sel !== '#zmMontageLayout') return noop;
    const wrapper = {
      val(v) {
        if (v === undefined) return state.select;
        state.select = String(v);
        return wrapper;
      },
      trigger() {
        return wrapper;
      },
    };
    return wrapper;
  };
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: jq,
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: () => ({addEventListener() {}, value: state.select}),
      querySelectorAll: () => [],
      addEventListener() {},
    },
    panZoomEnabled: false,
    user: {Id: 5},
    layouts: {12: {Name: 'Night', UserId: '5'}, 13: {Name: 'Day', UserId: '5'}},
    monitors: [],
    objGridStack: {enable() {}, disable() {}},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

test('changing the layout select while editing is put back', () => {
  const {sandbox, state} = loadMontage();
  sandbox.edit_layout({});
  state.select = '13';
  sandbox.selectLayout();
  assert.strictEqual(state.select, '12');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
