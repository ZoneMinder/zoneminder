'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/console.js');
const src = fs.readFileSync(file, 'utf8');

let selections = [];
const stub = {
  bootstrapTable(cmd) {
    return cmd === 'getSelections' ? selections : stub;
  },
  on() {
    return stub;
  },
  ready() {}, show() {}, find() {
    return {each() {}};
  }, attr() {}, parent() {
    return stub;
  },
  length: 0,
};

const mkBtn = () => ({disabled: true});
const actionInput = {value: ''};
const appended = [];
let submitted = false;
const form = {
  editBtn: mkBtn(), deleteBtn: mkBtn(), selectBtn: mkBtn(), cloneBtn: mkBtn(),
  restartBtn: mkBtn(),
  elements: {action: actionInput},
  appendChild(el) {
    appended.push(el);
  },
  submit() {
    submitted = true;
  },
};

const sandbox = {
  $j: () => stub,
  document: {
    forms: {monitorForm: form},
    createElement(tag) {
      return {tagName: tag, type: '', name: '', value: ''};
    },
  },
  window: {}, getCookie: () => 'x', consoleRefreshTimeout: 0, showDonatePopup: false,
  icons: {}, setInterval() {}, console,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

selections = [];
sandbox.setButtonStates();
assert.strictEqual(form.restartBtn.disabled, true, 'no selection disables Restart');
assert.strictEqual(submitted, false, 'no selection does not submit');

selections = [{_id: 4}, {_id: 7}];
sandbox.setButtonStates();
assert.strictEqual(form.restartBtn.disabled, false, 'a selection enables Restart');

sandbox.restartMonitor({form});
assert.strictEqual(submitted, true, 'Restart submits the console form');
assert.strictEqual(actionInput.value, 'restart', 'posts the restart action');
assert.deepStrictEqual(
    appended.map((input) => [input.name, input.value]),
    [['markMids[]', 4], ['markMids[]', 7]],
    'posts every selected monitor id');

console.log('ok console restart posts the selected monitors');
