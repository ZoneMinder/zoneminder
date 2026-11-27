'use strict';

// The reports table never stopped its loading indicator when the query failed.
// A result=Error answer (permission failure) returned without params.success()
// and left "Loading, please wait" up for ever, and a failed request called
// refresh, which re-ran the same request, so a persistent failure retried
// without end.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/reports.js'), 'utf8');

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

function loadReports() {
  const state = {tableCalls: [], ajaxFails: [], alerts: [], done: null, fail: null};

  function chainable() {
    const obj = {
      bootstrapTable: function(action) {
        state.tableCalls.push(action);
        return obj;
      },
      on: () => obj, ready: () => obj, prop: () => obj, show: () => obj,
      length: 0,
    };
    return obj;
  }
  const $j = function() {
    return chainable();
  };
  $j.getJSON = () => {
    const req = {
      done: (fn) => {
        state.done = fn;
        return req;
      },
      fail: (fn) => {
        state.fail = fn;
        return req;
      },
    };
    return req;
  };
  $j.each = (arr, fn) => (arr || []).forEach((v, i) => fn(i, v));

  const globals = {
    Object: Object, Array: Array, JSON: JSON, Math: Math, Date: Date,
    $j: $j,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    thisUrl: '/zm/index.php',
    logAjaxFail: (...args) => state.ajaxFails.push(args),
    alert: (message) => state.alerts.push(message),
    deferTableRequestWhileHidden: () => false,
    document: {getElementById: () => null},
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
  vm.runInContext(src, sandbox, {filename: 'reports.js'});
  return {sandbox, state};
}

console.log('reports table query');
test('a result=Error answer stops the loading indicator', () => {
  const {sandbox, state} = loadReports();
  sandbox.ajaxRequest({data: {}, success: () => {
    throw new Error('params.success must not be called for result=Error');
  }});
  state.tableCalls.length = 0;
  state.done({result: 'Error', message: 'Insufficient permissions'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.deepStrictEqual(state.alerts, ['Insufficient permissions']);
});
test('a failed request stops the loading indicator without refreshing', () => {
  const {sandbox, state} = loadReports();
  sandbox.ajaxRequest({data: {}});
  state.tableCalls.length = 0;
  state.fail({status: 500, statusText: 'Internal Server Error'}, 'error', 'Internal Server Error');
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.ok(!state.tableCalls.includes('refresh'),
      'refresh re-runs the failing request, so it retries for ever');
  assert.strictEqual(state.ajaxFails.length, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
