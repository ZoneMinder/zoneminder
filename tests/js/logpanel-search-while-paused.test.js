'use strict';

// Pausing auto-refresh (the toolbar autoRefresh button) used to drop every
// request that did not set allowRequest, including the one bootstrap-table
// issues for a search or advanced search. Typing in the search box then left
// stale rows and a stuck loading overlay. Only a scheduled refresh repeats the
// last query, so only that one may be skipped while paused.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.LOGPANEL_JS ||
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');

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

// Run initLogPanel against stub elements and report the bootstrap-table init
// options, the registered handlers, the autoRefresh button and the AJAX calls.
function initPanel() {
  const tableCalls = [];
  const handlers = {};
  const ajaxCalls = [];
  const btnHandlers = {};

  const icon = {
    classes: new Set(),
    classList: {
      add(c) {
        icon.classes.add(c);
      },
      remove(c) {
        icon.classes.delete(c);
      },
      toggle(c) {
        if (icon.classes.has(c)) icon.classes.delete(c);
        else icon.classes.add(c);
      },
      contains(c) {
        return icon.classes.has(c);
      },
    },
  };
  const btnAutoRefresh = {
    addEventListener: (ev, fn) => {
      btnHandlers[ev] = fn;
    },
    querySelector: (sel) => (sel === 'i.fa' ? icon : null),
  };

  function stub(selector) {
    const s = {
      length: 0,
      on: (ev, fn) => {
        handlers[selector + '|' + ev] = fn;
        return s;
      },
      one: (ev, fn) => {
        handlers[selector + '|' + ev] = fn;
        return s;
      },
      ready: () => s,
      find: (sel) => stub(sel),
      prop: () => s,
      attr: () => s,
      val: () => null,
      text: () => '',
      html: () => '',
      datetimepicker: () => s,
      bootstrapTable: (...args) => {
        tableCalls.push(args);
        if (args[0] === 'getSelections') return [];
        if (args[0] === 'getOptions') return {pageNumber: 1, pageSize: 25};
        return s;
      },
    };
    return s;
  }

  const $j = function(arg) {
    const s = stub(typeof arg === 'string' ? arg : 'element');
    if (arg && arg.classList) {
      s.toggleClass = (c) => {
        arg.classList.toggle(c);
        return s;
      };
      s.addClass = (c) => {
        arg.classList.add(c);
        return s;
      };
      s.removeClass = (c) => {
        arg.classList.remove(c);
        return s;
      };
    }
    return s;
  };
  $j.ready = () => {};
  $j.map = (arr, fn) => (arr || []).map(fn);
  $j.ajax = (opts) => {
    ajaxCalls.push(opts);
    return {readyState: 4, abort() {}};
  };

  const globals = {
    Object, Array, JSON, Math, Date, $j,
    document: {referrer: '', visibilityState: 'visible',
      getElementById: () => null},
    console: {log() {}, warn() {}, error() {}, debug() {}},
    icons: {},
    thisUrl: 'index.php',
    deferTableRequestWhileHidden: () => false,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'logpanel.js'});

  const panel = {
    dataset: {},
    querySelector: (sel) =>
      (sel === 'button[name="autoRefresh"]' ? btnAutoRefresh : null),
  };
  vm.runInContext('initLogPanel', sandbox)(panel);

  const init = tableCalls.find((c) =>
    c[0] && typeof c[0] === 'object' && typeof c[0].ajax === 'function');
  assert.ok(init, 'bootstrap-table received the ajax function at init');
  const ajax = init[0].ajax;

  // bootstrap-table fires pre-body before the first body, which is where the
  // panel wires the autoRefresh button.
  function pause() {
    const preBody = handlers['table.logPanel-logs|pre-body.bs.table'];
    assert.ok(preBody, 'pre-body handler registered');
    preBody();
    assert.ok(btnHandlers.click, 'autoRefresh button click handler registered');
    btnHandlers.click({});
  }

  function request(data) {
    const params = {data, success: () => {}};
    ajax(params);
  }

  return {ajaxCalls, pause, request};
}

console.log('auto-refresh paused search');

test('not paused: repeated requests are sent', () => {
  const {ajaxCalls, request} = initPanel();
  request({search: ''});
  request({search: ''});
  assert.strictEqual(ajaxCalls.length, 2,
      'both requests sent, got ' + ajaxCalls.length);
});

test('paused: a scheduled refresh repeating the last query is skipped', () => {
  const {ajaxCalls, pause, request} = initPanel();
  request({search: ''});
  assert.strictEqual(ajaxCalls.length, 1, 'the first request is sent');
  pause();
  request({search: ''});
  assert.strictEqual(ajaxCalls.length, 1,
      'the repeated request is dropped, got ' + ajaxCalls.length);
});

test('paused: a changed query is still sent', () => {
  const {ajaxCalls, pause, request} = initPanel();
  request({search: ''});
  assert.strictEqual(ajaxCalls.length, 1, 'the first request is sent');
  pause();
  request({search: 'zmc'});
  assert.strictEqual(ajaxCalls.length, 2,
      'the search is sent, got ' + ajaxCalls.length);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
