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
    confirmModal: {},
    button: {
      disabled: true,
      attrs: {},
      addEventListener: function(name, fn) {
        if (name === 'click') state.clearClickHandler = fn;
      },
      setAttribute: function(k, v) {
        this.attrs[k] = v;
      },
    },
    buttonLabel: {textContent: ''},
    confirmText: {textContent: ''},
    posts: [],
    clearClickHandler: null,
    filterInputs: {},
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
      prop: () => obj,
      addClass: () => obj,
      removeClass: () => obj,
      toggleClass: () => obj,
      attr: () => obj,
      val: () => null,
      length: 0,
    };
    return obj;
  }
  const $j = function(selector) {
    const obj = chainable();
    if (typeof selector === 'string' && selector in state.filterInputs) {
      obj.val = () => state.filterInputs[selector];
    }
    return obj;
  };
  $j.map = (arr, fn) => arr.map(fn);
  $j.ajax = (opts) => {
    state.posts.push(opts);
  };
  $j.getJSON = () => ({done: () => ({fail: () => {}})});

  const globals = {
    // The sandbox proxy claims every identifier, so built-ins have to be handed
    // through explicitly or log.js sees them as undefined.
    Object: Object, Array: Array, JSON: JSON, Math: Math, Date: Date,
    $j: $j,
    console: {log() {}, warn() {}, error() {}},
    thisUrl: '/zm/index.php',
    translate: {
      'ConfirmClearLogs': 'selected?',
      'ConfirmClearAllLogs': 'all?',
      'ClearLogs': 'Clear Logs',
      'ClearAllLogs': 'Clear ALL Logs',
      'ClearFilteredLogs': 'Clear Filtered Logs',
      'ConfirmClearFilteredLogs': 'filtered?',
      'DeletingRowsFromTable': 'deleting',
      'Reason': 'reason',
      'ErrorDeletingRowFromLogTable': 'err',
      'AJAXRequestError': 'ajaxerr',
    },
    document: {
      getElementById: function(id) {
        if (id === 'clearLogsBtn') return state.button;
        if (id === 'clearLogsConfirm') return state.confirmModal;
        if (id === 'clearLogsBtnLabel') return state.buttonLabel;
        if (id === 'clearLogsConfirmText') return state.confirmText;
        // initPage() wires several other controls; a generic stub is enough for
        // it to run so the clear button's own handler gets registered.
        return {addEventListener() {}, disabled: false, querySelector: () => null,
          classList: {contains: () => false, add() {}, remove() {}}};
      },
      referrer: '',
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
    deferTableRequestWhileHidden: () => false,
    secsToTime: () => '0',
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

console.log('\nctrl-click bypass');
test('ctrl-click still skips the dialog for a selected delete', () => {
  const {sandbox, state} = loadLog();
  sandbox.initPage();
  state.selections = [{Id: 5}];
  const handler = state.clearClickHandler;
  assert.ok(handler, 'the clear button click handler should be registered');
  handler({ctrlKey: true, preventDefault() {}});
  assert.strictEqual(state.posts.length, 1, 'should have deleted immediately');
  assert.deepStrictEqual(Array.from(state.posts[0].data['ids[]']), [5]);
});
test('ctrl-click with nothing selected must still confirm', () => {
  // The button used to be disabled with an empty selection, so this shortcut
  // could not reach the clear-everything path. It can now.
  const {sandbox, state} = loadLog();
  sandbox.initPage();
  state.selections = [];
  state.clearClickHandler({ctrlKey: true, preventDefault() {}});
  assert.strictEqual(state.posts.length, 0,
      'clearing the whole table must never skip the confirmation');
});

console.log('\nfilter forwarding');
test('the real request path is what populates the filter', () => {
  // rememberFilter has exactly one production caller, ajaxRequest. Driving it by
  // hand elsewhere would let the wiring break without a test noticing, and the
  // filter silently not being sent means a clear-filtered becomes a clear-all.
  const {sandbox, state} = loadLog();
  state.filterInputs = {
    '#filterServerId': '4',
    '#filterLevel': ['ERR'],
    '#filterComponent': ['zmc'],
    '#filterStartDateTime': '2026-01-01 00:00:00',
    '#filterEndDateTime': '',
  };
  sandbox.allowRequest = true;
  sandbox.autoRefresh = true;
  sandbox.ajaxRequest({data: {limit: 100, offset: 0, search: 'boom'}});

  assert.strictEqual(sandbox.filterIsActive(), true);
  sandbox.deleteLogs([]);
  const data = state.posts[state.posts.length - 1].data;
  assert.strictEqual(data.ServerId, '4');
  assert.deepStrictEqual(Array.from(data.level), ['ERR']);
  assert.deepStrictEqual(Array.from(data.Component), ['zmc']);
  assert.strictEqual(data.StartDateTime, '2026-01-01 00:00:00');
  assert.strictEqual(data.search, 'boom');
  assert.ok(!('EndDateTime' in data), 'an empty date is not a filter');
  assert.ok(!('limit' in data));
});

test('a clear all with no filter sends only all', () => {
  const {sandbox, state} = loadLog();
  sandbox.rememberFilter({limit: 100, offset: 0});
  sandbox.deleteLogs([]);
  assert.deepStrictEqual(Object.keys(state.posts[0].data).sort(), ['all'],
      'paging params must not be mistaken for a filter');
});
test('a clear all under a filter sends that filter', () => {
  const {sandbox, state} = loadLog();
  sandbox.rememberFilter({
    limit: 100, offset: 0, sort: 'TimeKey',
    level: ['ERR'], ServerId: '4', StartDateTime: '2026-01-01 00:00:00',
  });
  sandbox.deleteLogs([]);
  const data = state.posts[0].data;
  assert.strictEqual(data.all, 1);
  assert.deepStrictEqual(Array.from(data.level), ['ERR']);
  assert.strictEqual(data.ServerId, '4');
  assert.strictEqual(data.StartDateTime, '2026-01-01 00:00:00');
  assert.ok(!('sort' in data), 'only filter keys should be forwarded');
  assert.ok(!('limit' in data));
});
test('empty filter values are not treated as a filter', () => {
  const {sandbox} = loadLog();
  sandbox.rememberFilter({search: '', ServerId: null, level: undefined});
  assert.strictEqual(sandbox.filterIsActive(), false,
      'an empty search box must not turn clear all into a filtered delete');
});
test('deleting by id is unaffected by the filter', () => {
  const {sandbox, state} = loadLog();
  sandbox.rememberFilter({ServerId: '4'});
  sandbox.deleteLogs([1, 2]);
  assert.ok(!('ServerId' in state.posts[0].data));
  assert.ok(!('all' in state.posts[0].data));
});

console.log('\nbutton label');
test('says clear all when nothing is selected', () => {
  const {sandbox, state} = loadLog();
  state.selections = [];
  sandbox.manageClearButtonAvailability();
  assert.strictEqual(state.buttonLabel.textContent, 'Clear ALL Logs',
      'the difference should be visible before the confirmation, not only in it');
  assert.strictEqual(state.button.attrs.title, 'Clear ALL Logs');
});
test('says clear filtered when a filter is applied', () => {
  const {sandbox, state} = loadLog();
  state.selections = [];
  sandbox.rememberFilter({ServerId: '4'});
  sandbox.manageClearButtonAvailability();
  assert.strictEqual(state.buttonLabel.textContent, 'Clear Filtered Logs',
      'must not promise ALL when it will only clear the filtered rows');
});
test('says clear logs once rows are selected', () => {
  const {sandbox, state} = loadLog();
  state.selections = [{Id: 7}];
  sandbox.manageClearButtonAvailability();
  assert.strictEqual(state.buttonLabel.textContent, 'Clear Logs');
  assert.strictEqual(state.button.attrs.title, 'Clear Logs');
});

console.log('\nconfirmation wording');
test('says filtered in the confirmation too', () => {
  const {sandbox, state} = loadLog();
  state.selections = [];
  sandbox.rememberFilter({ServerId: '4'});
  sandbox.updateClearLogsConfirmText();
  assert.strictEqual(state.confirmText.textContent, 'filtered?');
});
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
