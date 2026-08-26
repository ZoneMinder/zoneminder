'use strict';

// The Clear Logs button started out disabled and only enabled once rows were
// selected, so pressing it on a freshly loaded Log view did nothing at all: no
// dialog, no request, no error.  Nothing selected now means clear all, sent as
// a single request rather than 100 ids at a time.
// refs #4727

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/log.js'), 'utf8');

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

// log.js is a browser file full of globals from skin.js and the view template.
// Resolve unknown ones to undefined and give real values only to what these
// tests touch.  initPage() is never reached: the stub $j(...).ready() drops the
// callback, so nothing below the function definitions runs.
function loadLog() {
  const state = {
    selections: [],
    button: {disabled: true},
    confirmText: {textContent: ''},
    posts: [],
  };

  function chainable() {
    const obj = {
      bootstrapTable: function(action) {
        return action === 'getSelections' ? state.selections : obj;
      },
      on: () => obj,
      one: () => obj,
      ready: () => obj, // swallow $j(document).ready(initPage)
      modal: () => obj,
      text: () => '', // readHeaderRequestStatus() calls .text().replace()
      find: () => obj,
      html: () => obj,
      datetimepicker: () => obj,
      length: 0,
    };
    return obj;
  }
  const $j = function() {
    return chainable();
  };
  $j.map = (arr, fn) => arr.map(fn);
  $j.ajax = (opts) => {
    state.posts.push(opts);
  };
  $j.getJSON = () => ({done: () => ({fail: () => {}})});

  const globals = {
    $j: $j,
    console: {log() {}, warn() {}, error() {}},
    thisUrl: '/zm/index.php',
    translate: {
      'ConfirmClearLogs': 'selected?',
      'ConfirmClearAllLogs': 'all?',
      'DeletingRowsFromTable': 'deleting',
      'Reason': 'reason',
      'ErrorDeletingRowFromLogTable': 'err',
      'AJAXRequestError': 'ajaxerr',
    },
    document: {
      getElementById: function(id) {
        if (id === 'clearLogsBtn') return state.button;
        if (id === 'clearLogsConfirmText') return state.confirmText;
        return null;
      },
      querySelector: () => null,
      createElement: () => ({
        classList: {add() {}},
        appendChild() {},
        querySelector: () => ({style: {}}),
        style: {},
      }),
      addEventListener: () => {},
    },
    zmAlert: () => 'alertId',
    closeZmAlert: () => {},
    waitUntil: () => ({then: () => ({catch: () => {}})}),
    updateHeaderRequestStatus: () => {},
    readHeaderRequestStatus: () => 'awaiting',
    logAjaxFail: () => {},
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
  };
  const sandbox = new Proxy(globals, {
    has: () => true, // every bare identifier resolves against this object
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'log.js'});
  return {sandbox: sandbox, state: state};
}

console.log('clear logs button availability');
test('stays enabled with nothing selected', () => {
  const {sandbox, state} = loadLog();
  state.selections = [];
  sandbox.manageClearButtonAvailability();
  assert.strictEqual(state.button.disabled, false,
      'an empty selection now means clear all, so the button must be usable');
});
test('is enabled with rows selected', () => {
  const {sandbox, state} = loadLog();
  state.selections = [{Id: 7}];
  sandbox.manageClearButtonAvailability();
  assert.strictEqual(state.button.disabled, false);
});
test('is still disabled while a request is in flight', () => {
  const {sandbox, state} = loadLog();
  state.selections = [{Id: 7}];
  sandbox.manageClearButtonAvailability(false);
  assert.strictEqual(state.button.disabled, true,
      'explicit disable is what suppresses the button during a refresh');
});

console.log('\nconfirmation wording');
test('warns that everything goes when nothing is selected', () => {
  const {sandbox, state} = loadLog();
  state.selections = [];
  sandbox.updateClearLogsConfirmText();
  assert.strictEqual(state.confirmText.textContent, 'all?');
});
test('mentions the selection when there is one', () => {
  const {sandbox, state} = loadLog();
  state.selections = [{Id: 7}];
  sandbox.updateClearLogsConfirmText();
  assert.strictEqual(state.confirmText.textContent, 'selected?');
});

console.log('\ndeleteLogs');
test('an empty selection clears all in one request', () => {
  const {sandbox, state} = loadLog();
  sandbox.deleteLogs([]);
  assert.strictEqual(state.posts.length, 1, 'expected exactly one request');
  const data = state.posts[0].data;
  assert.strictEqual(data.all, 1,
      'must ask the backend to clear the table, not post an empty id list');
  assert.ok(!('ids[]' in data), 'must not send an empty id list');
  assert.strictEqual(state.posts[0].method, 'post');
});
test('a selection still deletes by id', () => {
  const {sandbox, state} = loadLog();
  sandbox.deleteLogs([1, 2, 3]);
  assert.strictEqual(state.posts.length, 1);
  assert.deepStrictEqual(Array.from(state.posts[0].data['ids[]']), [1, 2, 3]);
  assert.ok(!('all' in state.posts[0].data));
});
test('a selection larger than a chunk is still batched', () => {
  const {sandbox, state} = loadLog();
  const ids = [];
  for (let i = 0; i < 150; i++) ids.push(i);
  sandbox.deleteLogs(ids);
  assert.strictEqual(state.posts[0].data['ids[]'].length, 100,
      'the id path must keep chunking at 100');
  assert.ok(!('all' in state.posts[0].data),
      'a selected delete must never turn into a clear all');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
