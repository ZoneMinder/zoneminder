'use strict';

// The snapshots table drops its selection on every reload (refresh, search,
// sort, page change) without firing an uncheck event, so the Delete button
// stayed enabled with nothing selected.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/snapshots.js');
const src = fs.readFileSync(file, 'utf8');

function button() {
  return {disabled: true, prop(k, v) {
    this[k] = v;
  }, click() {}, on() {}};
}

let selected = [];
const handlers = {};
const table = {
  bootstrapTable: (cmd) => (cmd === 'getSelections' ? selected : undefined),
  on(names, fn) {
    names.split(/\s+/).forEach((n) => (handlers[n] = handlers[n] || []).push(fn));
    return table;
  },
  show() {},
};
const buttons = {'#backBtn': button(), '#exportBtn': button(),
  '#deleteBtn': button(), '#snapshotTable': table};
const el = () => ({addEventListener() {}, ready() {}, click() {}, on() {}, prop() {}});
const sandbox = {
  $j: Object.assign((sel) => buttons[sel] || el(), {map: () => [], ajaxSetup() {}}),
  canView: {Events: true, Snapshots: true}, canEdit: {Events: true, Snapshots: true},
  icons: {}, WEB_LIST_THUMBS: true,
  document: {referrer: '', getElementById: el, querySelectorAll: () => []},
  window: {}, console, thisUrl: '?', strip_html: (s) => s,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
try {
  vm.runInContext('initPage()', sandbox);
} catch {
  // Only the toolbar binding at the top of initPage matters here.
}

// Fire an event the way bootstrap-table does. Other handlers on the same event
// (thumbnail set-up on post-body) need a real DOM, so their errors are ignored.
const fire = (name) => {
  assert.ok(handlers[name], name + ' is not bound');
  for (const fn of handlers[name]) {
    try {
      fn();
    } catch {
      // not the toolbar handler
    }
  }
};

selected = [{Id: 1}];
fire('check.bs.table');
assert.strictEqual(buttons['#deleteBtn'].disabled, false, 'delete enabled after select');

// Reload: selection is gone, no uncheck event fires, only the body is redrawn
selected = [];
fire('post-body.bs.table');
assert.strictEqual(buttons['#deleteBtn'].disabled, true, 'delete disabled after reload');
console.log('ok - snapshots delete button follows the selection across reloads');
