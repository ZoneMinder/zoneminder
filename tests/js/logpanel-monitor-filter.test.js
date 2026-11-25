'use strict';

// The Log view gained a Monitor filter beside Component. The select only means
// something if its value reaches the query, and if a "clear all" repeats it so
// the rows deleted are the rows that were showing.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');

function initPanel(values) {
  let ajaxFn = null;
  function stub(selector) {
    const s = {
      length: 0,
      on: () => s,
      one: () => s,
      ready: () => s,
      find: (sel) => stub(sel),
      prop: () => s,
      attr: () => s,
      replaceWith: () => s,
      clone: () => s,
      val: () => (Object.prototype.hasOwnProperty.call(values, selector) ? values[selector] : null),
      text: () => '',
      html: () => '',
      datetimepicker: () => s,
      bootstrapTable: (arg) => {
        if (arg && typeof arg === 'object' && typeof arg.ajax === 'function') ajaxFn = arg.ajax;
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
  $j.ajax = () => ({readyState: 0, abort() {}});
  const globals = {
    Object, Array, JSON, Math, Date, $j,
    thisUrl: 'index.php',
    deferTableRequestWhileHidden: () => false,
    document: {referrer: '', visibilityState: 'visible', getElementById: () => null},
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
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'logpanel.js'});
  const panel = {dataset: {}, querySelector: () => null};
  vm.runInContext('initLogPanel', sandbox)(panel);
  return {ajax: ajaxFn, sandbox};
}

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

console.log('monitor filter');
test('the chosen monitor is sent with the query', () => {
  const {ajax} = initPanel({'.logPanel-monitor': '5'});
  const params = {data: {}};
  ajax(params);
  assert.strictEqual(params.data.MonitorId, '5');
});

test('no monitor sends no MonitorId', () => {
  const {ajax} = initPanel({});
  const params = {data: {}};
  ajax(params);
  assert.ok(!Object.prototype.hasOwnProperty.call(params.data, 'MonitorId'),
      'MonitorId not sent: ' + JSON.stringify(params.data));
});

test('a clear all repeats the monitor filter', () => {
  const {sandbox} = initPanel({});
  assert.strictEqual(sandbox.logPanelFilterOf({MonitorId: '5', offset: 0}).MonitorId, '5');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
