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

function loadMontage(canEdit, layoutOwner) {
  const state = {alerts: [], submits: 0};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    alert: (msg) => state.alerts.push(msg),
    $j: makeChainable(),
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener() {},
    },
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    ZM_PRESET_LAYOUT_NAMES: ['Auto', '1 Wide'],
    user: {Id: 5},
    canEdit,
    layouts: {12: {Name: 'Night', UserId: layoutOwner}},
    objGridStack: {save: () => []},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  const form = {
    elements: {
      Name: {value: ''},
      zmMontageLayout: {options: [{text: 'Night'}], selectedIndex: 0},
    },
    zmMontageLayout: {value: '12'},
    Positions: {},
    submit() {
      state.submits++;
    },
  };
  return {save: () => sandbox.save_layout({form}), state};
}

test("saving over another user's layout is refused with an alert", () => {
  const {save, state} = loadMontage({System: false}, '7');
  save();
  assert.strictEqual(state.submits, 0);
  assert.strictEqual(state.alerts.length, 1);
});

test('saving over a layout without an owner is refused for a normal user', () => {
  const {save, state} = loadMontage({System: false}, '0');
  save();
  assert.strictEqual(state.submits, 0);
});

test('saving over your own layout goes through', () => {
  const {save, state} = loadMontage({System: false}, '5');
  save();
  assert.strictEqual(state.submits, 1);
  assert.strictEqual(state.alerts.length, 0);
});

test("a user with System edit can save over another user's layout", () => {
  const {save, state} = loadMontage({System: true}, '7');
  save();
  assert.strictEqual(state.submits, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
