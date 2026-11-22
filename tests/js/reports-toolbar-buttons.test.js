'use strict';

// The reports table drops its selection on every reload (refresh, search,
// sort, page change) without firing an uncheck event, so the View and Delete
// buttons stayed enabled with nothing selected.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/reports.js');
const src = fs.readFileSync(file, 'utf8');

function button() {
  return {disabled: true, prop(k, v) {
    this[k] = v;
  }};
}

let selected = [];
const handlers = {};
const table = {
  bootstrapTable: (cmd) => (cmd === 'getSelections' ? selected : undefined),
  on(names, fn) {
    names.split(/\s+/).forEach((n) => handlers[n] = fn);
    return table;
  },
  show() {},
};
const buttons = {'#backBtn': button(), '#viewBtn': button(), '#editBtn': button(),
  '#deleteBtn': button(), '#reportsTable': table};
const el = () => ({addEventListener() {}, ready() {}});
const sandbox = {
  $j: Object.assign((sel) => buttons[sel] || el(), {map: () => []}),
  canView: {Events: true}, canEdit: {Events: true}, icons: {},
  document: {referrer: '', getElementById: el}, window: {}, console,
  thisUrl: '?', strip_html: (s) => s,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
vm.runInContext('initPage()', sandbox);

// Fire an event the way bootstrap-table does
const fire = (name) => {
  assert.ok(handlers[name], name + ' is not bound');
  handlers[name]();
};

selected = [{Id: 1}];
fire('check.bs.table');
assert.strictEqual(buttons['#viewBtn'].disabled, false, 'view enabled after select');
assert.strictEqual(buttons['#deleteBtn'].disabled, false, 'delete enabled after select');

// Reload: selection is gone, no uncheck event fires, only the body is redrawn
selected = [];
fire('post-body.bs.table');
assert.strictEqual(buttons['#viewBtn'].disabled, true, 'view disabled after reload');
assert.strictEqual(buttons['#deleteBtn'].disabled, true, 'delete disabled after reload');
console.log('ok - reports buttons follow the selection across reloads');
