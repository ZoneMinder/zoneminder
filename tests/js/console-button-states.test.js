'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/console.js');
const src = fs.readFileSync(file, 'utf8');

let selections = [];
const handlers = {};
const stub = {
  bootstrapTable(cmd) {
    return cmd === 'getSelections' ? selections : stub;
  },
  on(events, fn) {
    events.split(' ').forEach((e) => {
      (handlers[e] = handlers[e] || []).push(fn);
    });
    return stub;
  },
  ready() {}, show() {}, sortable() {}, find() {
    return {each() {}};
  }, attr() {}, parent() {
    return stub;
  },
  length: 0,
};
const mkBtn = () => ({disabled: true});
const form = {editBtn: mkBtn(), deleteBtn: mkBtn(), selectBtn: mkBtn(), cloneBtn: mkBtn(), restartBtn: mkBtn()};

const sandbox = {
  $j: () => stub, document: {forms: {monitorForm: form}}, window: {},
  getCookie: () => 'x', consoleRefreshTimeout: 0, showDonatePopup: false,
  icons: {}, setInterval() {}, console,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
sandbox.initPage();

function fire(ev) {
  (handlers[ev] || []).forEach((fn) => fn());
}
function states() {
  return ['editBtn', 'deleteBtn', 'selectBtn', 'cloneBtn', 'restartBtn'].map((b) => form[b].disabled);
}

selections = [{_id: 1}];
fire('check.bs.table');
assert.deepStrictEqual(states(), [false, false, false, false, false], 'one row enables all');

selections = [{_id: 1}, {_id: 2}];
fire('check.bs.table');
assert.deepStrictEqual(states(), [false, false, false, true, false], 'clone needs exactly one row');

// A refresh, filter change or page change reloads the rows and drops the selection
selections = [];
fire('load-success.bs.table');
assert.deepStrictEqual(states(), [true, true, true, true, true], 'reload with no selection disables all');
console.log('ok console button states follow the selection');
