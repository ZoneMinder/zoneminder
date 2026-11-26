'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/add_monitors.js'), 'utf8');

function load() {
  const state = {tableCalls: [], alerts: [], opts: null};
  const fakeEl = {
    on: () => {},
    each: () => {},
    ready: () => {},
    show: () => {},
    bootstrapTable: (cmd) => state.tableCalls.push(cmd),
  };
  const sandbox = {
    $j: Object.assign(function() {
      return fakeEl;
    }, {
      ajax: (opts) => {
        state.opts = opts;
      },
      each: (arr, fn) => (arr || []).forEach((v, i) => fn(i, v)),
    }),
    document: {getElementById: () => ({addEventListener() {}})},
    window: {},
    thisUrl: 'index.php',
    zmAuth: {},
    setCookie: () => {},
    logAjaxFail: () => {},
    alert: (message) => state.alerts.push(message),
    console: {log: () => {}},
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.table = fakeEl;
  return {sandbox, state};
}

{
  const {sandbox, state} = load();
  sandbox.ajaxRequest({data: {}, success: () => {
    throw new Error('params.success must not be called for result=Error');
  }});
  state.opts.success({result: 'Error', message: 'Insufficient permissions'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.deepStrictEqual(state.alerts, ['Insufficient permissions']);
  console.log('ok - a result=Error answer stops the loading indicator');
}

{
  const {sandbox, state} = load();
  sandbox.ajaxRequest({data: {}});
  state.opts.error({status: 500, statusText: 'Internal Server Error'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  console.log('ok - a failed probe stops the loading indicator');
}
