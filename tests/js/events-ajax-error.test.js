'use strict';

// A large events query can exhaust the PHP memory limit, so the request 500s.
// events.js only console.logged that, and never called hideLoading(), so the
// table sat on "Loading, please wait" for ever with nothing saying what had
// happened. refs #3301

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/events.js'), 'utf8');

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
// Resolve unknown ones to undefined and give real values only to what is needed
// to reach the ajax error handler.
function loadEvents() {
  const state = {tableCalls: [], ajaxFails: [], opts: null};

  function chainable() {
    const obj = {
      bootstrapTable: function(action) {
        state.tableCalls.push(action);
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
  $j.getJSON = () => ({done: () => ({fail: () => {}})});

  const globals = {
    Object: Object, Array: Array, JSON: JSON, Math: Math, Date: Date,
    $j: $j,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    thisUrl: '/zm/index.php',
    filterQuery: '',
    logAjaxFail: (jqXHR) => state.ajaxFails.push(jqXHR),
    deferTableRequestWhileHidden: () => false,
    setTimeout: () => 0, clearTimeout: () => 0,
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
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
  vm.runInContext(src, sandbox, {filename: 'events.js'});
  return {sandbox, state};
}

// Drive the real ajaxRequest so the handler under test is the shipped one.
function errorHandler(sandbox, state) {
  sandbox.ajaxRequest({data: {}});
  assert.ok(state.opts && state.opts.error, 'ajaxRequest should register an error handler');
  state.tableCalls.length = 0;
  return state.opts.error;
}

console.log('events table ajax failure');
test('a failed query stops the loading indicator', () => {
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({status: 500, statusText: 'Internal Server Error'});
  assert.ok(state.tableCalls.includes('hideLoading'),
      'the table must not be left saying "Loading, please wait"');
});
test('and reports the failure', () => {
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({status: 500, statusText: 'Internal Server Error'});
  assert.strictEqual(state.ajaxFails.length, 1);
});
test('an aborted request is not treated as a failure', () => {
  // Every reload aborts the request in flight; those must stay silent.
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({statusText: 'abort'});
  assert.deepStrictEqual(state.tableCalls, []);
  assert.strictEqual(state.ajaxFails.length, 0);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
