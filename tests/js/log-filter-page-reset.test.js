'use strict';

// Changing a Log filter (component, level, server, date range) while on a later
// page requested the same offset of the new result set, so the user landed on
// page 4 of the filtered rows instead of the first page.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');

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

// Run initLogPanel against stub elements and report the table calls and the
// handlers it registered.
function initPanel() {
  const tableCalls = [];
  const handlers = {};
  function stub(selector) {
    const s = {
      length: 0,
      on: (ev, fn) => {
        handlers[selector + '|' + ev] = fn;
        return s;
      },
      one: () => s,
      ready: () => s,
      find: (sel) => stub(sel),
      prop: () => s,
      attr: () => s,
      val: () => null,
      text: () => '',
      datetimepicker: () => s,
      bootstrapTable: (...args) => {
        tableCalls.push(args);
        if (args[0] === 'getSelections') return [];
        if (args[0] === 'getOptions') return {pageNumber: 4, pageSize: 25};
        return s;
      },
    };
    return s;
  }
  const $j = function() {
    return stub('panel');
  };
  $j.ready = () => {};
  $j.map = () => [];
  const globals = {
    Object, Array, JSON, Math, Date, $j,
    document: {referrer: '', getElementById: () => null},
    console: {log() {}, warn() {}, error() {}, debug() {}},
    icons: {},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  // $j(document).ready(...) is stubbed away, so call the initialiser directly.
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'logpanel.js'});
  const panel = {dataset: {}, querySelector: () => null};
  vm.runInContext('initLogPanel', sandbox)(panel);
  return {tableCalls, handlers};
}

console.log('filter change');
test('refreshes from the first page', () => {
  const {tableCalls, handlers} = initPanel();
  const change = handlers['.logPanel-filter|change'];
  assert.ok(change, 'filter change handler registered');
  tableCalls.length = 0;
  change();
  const refresh = tableCalls.find((c) => c[0] === 'refresh');
  assert.ok(refresh, 'table refreshed');
  assert.ok(refresh[1] && refresh[1].pageNumber === 1,
      'refresh asks for page 1, got ' + JSON.stringify(refresh[1]));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
