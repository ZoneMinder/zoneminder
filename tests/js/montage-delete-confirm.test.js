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

function loadMontage(canEdit) {
  const state = {submits: 0, enoperm: 0, click: null};
  const confirmBtn = {
    disabled: false,
    addEventListener(type, fn) {
      if (!state.click) state.click = fn;
    },
  };
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => {
      if (prop === 'then') return undefined;
      if (prop === '0') {
        return {form: {submit() {
          state.submits++;
        }}};
      }
      return proxy;
    },
    apply: () => proxy,
  });
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: proxy,
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: (id) => (id === 'delConfirmBtn' ? confirmBtn : {addEventListener() {}}),
      querySelectorAll: () => [],
      addEventListener() {},
    },
    enoperm: () => state.enoperm++,
    canEdit,
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.manageDelConfirmModalBtns();
  return {state, confirmBtn};
}

test('System edit permission is enough to confirm a layout delete', () => {
  const {state, confirmBtn} = loadMontage({System: true, Monitors: false});
  state.click({preventDefault() {}});
  assert.strictEqual(state.enoperm, 0);
  assert.strictEqual(state.submits, 1);
  assert.strictEqual(confirmBtn.disabled, true);
});

test('without System edit the delete is refused and the button stays usable', () => {
  const {state, confirmBtn} = loadMontage({System: false, Monitors: true});
  state.click({preventDefault() {}});
  assert.strictEqual(state.enoperm, 1);
  assert.strictEqual(state.submits, 0);
  assert.strictEqual(confirmBtn.disabled, false);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
