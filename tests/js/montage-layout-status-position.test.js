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

function makeSelect(values, initialValue) {
  const select = {
    options: values.map((value) => ({value})),
    selectedIndex: values.indexOf(initialValue),
  };
  select.options.forEach((option) => {
    Object.defineProperty(option, 'selected', {
      get() {
        return select.options[select.selectedIndex] === option;
      },
      set(selected) {
        if (selected) {
          select.selectedIndex = select.options.indexOf(option);
        } else if (select.options[select.selectedIndex] === option) {
          select.selectedIndex = -1;
        }
      },
    });
  });
  Object.defineProperty(select, 'value', {
    get() {
      return select.selectedIndex >= 0 ? select.options[select.selectedIndex].value : '';
    },
    set(value) {
      select.selectedIndex = select.options.findIndex((option) => option.value === value);
    },
  });
  return select;
}

function makeChainable() {
  return new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : makeChainable()),
    apply: () => makeChainable(),
  });
}

const STATUS_VALUES = ['insideImgBottom', 'outsideImgBottom', 'hidden', 'showOnHover'];

function loadMontage(initialStatus) {
  const state = {
    select: '12',
    statusSelect: makeSelect(STATUS_VALUES, initialStatus),
    cookies: {},
    chosenUpdated: 0,
    timers: [],
  };
  const noop = makeChainable();

  const statusWrapper = {
    val(value) {
      if (value === undefined) return state.statusSelect.value;
      state.statusSelect.value = String(value);
      return statusWrapper;
    },
    trigger(event) {
      if (event === 'chosen:updated') state.chosenUpdated++;
      return statusWrapper;
    },
  };

  const layoutWrapper = {
    val(value) {
      if (value === undefined) return state.select;
      state.select = String(value);
      return layoutWrapper;
    },
    trigger() {
      return layoutWrapper;
    },
  };

  const jq = (target) => {
    if (typeof target === 'string') {
      if (target === '#zmMontageLayout') return layoutWrapper;
      if (target === '#monitorStatusPosition') return statusWrapper;
      return noop;
    }
    if (target === state.statusSelect) return statusWrapper;
    return noop;
  };

  const btnDeleteLayout = {
    setAttribute() {},
    removeAttribute() {},
  };

  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: jq,
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: (id) => {
        if (id === 'btnDeleteLayout') return btnDeleteLayout;
        if (id === 'monitorStatusPosition') return state.statusSelect;
        return null;
      },
      querySelectorAll: () => [],
      addEventListener() {},
    },
    GridStack: {
      init: () => ({compact() {}, on() {}}),
    },
    ZM_PRESET_LAYOUT_NAMES: ['Auto', '1 Wide'],
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
    setCookie: (name, value) => {
      state.cookies[name] = String(value);
    },
    getCookie: (name) => state.cookies[name],
    setTimeout: (fn) => {
      state.timers.push(fn);
      return state.timers.length;
    },
    clearTimeout() {},
    innerWidth: 1000,
    innerHeight: 800,
    panZoomEnabled: false,
    user: {Id: 5},
    layouts: {
      12: {
        Name: 'Night',
        UserId: 5,
        Positions: {monitorStatusPosition: 'hidden'},
      },
      13: {
        Name: 'Day',
        UserId: 5,
        Positions: {},
      },
      14: {
        Name: 'Unknown',
        UserId: 5,
        Positions: {monitorStatusPosition: 'banana'},
      },
    },
    monitors: [],
    objGridStack: {destroy() {}, enable() {}, disable() {}},
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

test('selecting a custom layout applies its saved status position', () => {
  const {sandbox, state} = loadMontage('outsideImgBottom');
  sandbox.selectLayout(12);
  assert.strictEqual(state.statusSelect.value, 'hidden');
  assert.strictEqual(state.cookies.zmMonitorStatusPositionSelected, 'hidden');
  assert.ok(state.chosenUpdated > 0);
});

test('a layout without a saved status position leaves the select unchanged', () => {
  const {sandbox, state} = loadMontage('insideImgBottom');
  sandbox.selectLayout(13);
  assert.strictEqual(state.statusSelect.value, 'insideImgBottom');
  assert.strictEqual(state.cookies.zmMonitorStatusPositionSelected, undefined);
});

test('an unknown saved status position is ignored', () => {
  const {sandbox, state} = loadMontage('outsideImgBottom');
  sandbox.selectLayout(14);
  assert.strictEqual(state.statusSelect.value, 'outsideImgBottom');
  assert.strictEqual(state.cookies.zmMonitorStatusPositionSelected, undefined);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
