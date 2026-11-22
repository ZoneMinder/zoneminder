'use strict';

// ajaxError() in web/includes/functions.php answers with result=Error and a
// plain string message, while the per-event path in web/ajax/events.php returns
// an array. The delete and unarchive success handlers assumed an array and did
// data.message.join(), which throws TypeError on the string: the table is never
// refreshed and the confirm modal stays open with its button disabled.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.env.EVENTS_JS ||
  path.join(__dirname, '../../web/skins/classic/views/js/events.js');
const src = fs.readFileSync(file, 'utf8');

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

// events.js is a browser file leaning on globals from skin.js and the view.
function loadEvents() {
  const state = {tableCalls: [], alerts: [], modals: [], opts: null};

  function chainable() {
    const obj = {
      bootstrapTable: function(action) {
        state.tableCalls.push(action);
        return obj;
      },
      modal: function(action) {
        state.modals.push(action);
        return obj;
      },
      on: () => obj, one: () => obj, ready: () => obj, each: () => obj,
      attr: () => '', val: () => '', prop: () => obj, find: () => obj,
      html: () => obj, text: () => '', css: () => obj, length: 0,
    };
    return obj;
  }
  const $j = function() {
    return chainable();
  };
  $j.ajax = (o) => {
    state.opts = o;
    return {abort() {}};
  };
  $j.each = (arr, fn) => (arr || []).forEach((v, i) => fn(i, v));
  $j.map = (arr, fn) => (arr || []).map((v, i) => fn(v, i));
  $j.getJSON = () => ({done: () => ({fail: () => {}})});

  const globals = {
    Object: Object, Array: Array, JSON: JSON, Math: Math, Date: Date,
    String: String, Number: Number, URLSearchParams: URLSearchParams,
    $j: $j,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    thisUrl: '/zm/index.php',
    filterQuery: '',
    sortQuery: '',
    logAjaxFail: () => {},
    enoperm: () => {},
    insertModalHtml: () => {},
    alert: (message) => state.alerts.push(message),
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      referrer: '',
    },
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : t[p]),
    set: (t, p, v) => {
      t[p] = v;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: file});
  return {sandbox, state};
}

function run(fnName, response) {
  const {sandbox, state} = loadEvents();
  sandbox[fnName]([1]);
  assert.ok(state.opts && state.opts.success, fnName + ' should issue an ajax request');
  state.opts.success(response);
  return state;
}

['deleteEvents', 'unarchiveEvents'].forEach(function(fnName) {
  console.log(fnName + ' error message');
  test('a string message is alerted as is', () => {
    const state = run(fnName, {result: 'Error', message: 'nope'});
    assert.deepStrictEqual(state.alerts, ['nope']);
    assert.ok(state.tableCalls.includes('refresh'), 'the table must be refreshed');
    assert.ok(state.modals.includes('hide'), 'the confirm modal must be hidden');
  });
  test('an array message is joined with newlines', () => {
    const state = run(fnName, {result: 'Ok', message: ['a', 'b']});
    assert.deepStrictEqual(state.alerts, ['a\nb']);
    assert.ok(state.tableCalls.includes('refresh'));
    assert.ok(state.modals.includes('hide'));
  });
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
