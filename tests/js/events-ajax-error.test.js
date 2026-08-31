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
  const state = {tableCalls: [], ajaxFails: [], alerts: [], opts: null};

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
    logAjaxFail: (...args) => state.ajaxFails.push(args),
    zmAlert: (message, title) => state.alerts.push({message, title}),
    alert: (message) => state.alerts.push({message, title: undefined}),
    translate: {
      'Reason': 'Reason',
      'ErrorUpdatingEventTable': 'Error updating event table',
      'AJAXRequestError': 'AJAX request error',
    },
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
test('and says so where the user can see it', () => {
  // logAjaxFail only writes to the console, and events.php suppresses
  // display_errors, so a memory-limit fatal arrives with an empty body and even
  // the console line is just "No responseText". The alert is the only thing the
  // reporter of #3301 would actually see.
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({status: 500, statusText: 'Internal Server Error'});
  assert.strictEqual(state.alerts.length, 1);
  assert.ok(state.alerts[0].message.includes('Error updating event table'));
  assert.strictEqual(state.alerts[0].title, 'AJAX request error');
});
test('and logs it with all three callback arguments', () => {
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({status: 500, statusText: 'err'}, 'error', 'Internal Server Error');
  assert.strictEqual(state.ajaxFails.length, 1);
  assert.deepStrictEqual(state.ajaxFails[0].slice(1), ['error', 'Internal Server Error'],
      'dropping these makes the console line read "Request Failed: undefined, undefined"');
});
test('an aborted request is not treated as a failure', () => {
  // Every reload aborts the request in flight; those must stay silent.
  const {sandbox, state} = loadEvents();
  errorHandler(sandbox, state)({statusText: 'abort'});
  assert.deepStrictEqual(state.tableCalls, []);
  assert.strictEqual(state.ajaxFails.length, 0);
});

console.log('\nsuccess handler reporting an error');
test('a result=Error response also stops the loading indicator', () => {
  // ajaxError() answers 200 with result=Error for a permission failure, so this
  // lands in the success handler and returns without params.success().
  const {sandbox, state} = loadEvents();
  sandbox.ajaxRequest({data: {}, success: () => {
    throw new Error('params.success must not be called for result=Error');
  }});
  state.tableCalls.length = 0;
  state.opts.success({result: 'Error', message: 'Insufficient permissions'});
  assert.ok(state.tableCalls.includes('hideLoading'),
      'this path left the table loading for ever too');
  assert.strictEqual(state.alerts.length, 1);
  assert.strictEqual(state.alerts[0].message, 'Insufficient permissions');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
