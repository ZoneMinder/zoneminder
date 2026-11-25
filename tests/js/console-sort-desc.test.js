'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/console.js');
const src = fs.readFileSync(file, 'utf8');

let captured = null;
const sortOptions = {sortName: 'Sequence', sortOrder: 'desc'};
const stub = {
  bootstrapTable(cmd) {
    return cmd === 'getOptions' ? sortOptions : stub;
  },
  on() {
    return stub;
  },
  ready() {},
  show() {},
  find() {
    return {each() {}};
  }, attr() {}, parent() {
    return stub;
  },
  sortable(cmd) {
    return cmd === 'toArray' ? ['3', '2', '1'] : stub;
  },
  length: 0,
};
const $j = () => stub;
$j.post = function(url, data) {
  captured = data;
  return {fail() {}};
};

const sandbox = {
  $j, document: {forms: {}}, window: {},
  getCookie: () => 'x', consoleRefreshTimeout: 0, showDonatePopup: false,
  icons: {}, setInterval() {}, console, thisUrl: 'x', logAjaxFail() {},
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

sortOptions.sortName = 'Sequence';
sortOptions.sortOrder = 'desc';
captured = null;
sandbox.applySort.call(stub, null, null);
assert.deepStrictEqual(captured.monitor_ids, ['1', '2', '3'], 'desc sequence reverses the posted order');
assert.strictEqual(captured.action, 'sort');

sortOptions.sortOrder = 'asc';
captured = null;
sandbox.applySort.call(stub, null, null);
assert.deepStrictEqual(captured.monitor_ids, ['3', '2', '1'], 'asc sequence keeps the posted order');

sortOptions.sortName = 'Name';
sortOptions.sortOrder = 'desc';
captured = null;
sandbox.applySort.call(stub, null, null);
assert.deepStrictEqual(captured.monitor_ids, ['3', '2', '1'], 'only sequence desc reverses');

console.log('ok console sort desc');
