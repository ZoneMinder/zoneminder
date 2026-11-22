'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/snapshots.js'), 'utf8');

function load() {
  const state = {tableCalls: [], alerts: [], ajaxFails: 0, req: null};
  const fakeEl = {
    prop: () => {},
    on: () => {},
    ready: () => {},
    bootstrapTable: (cmd) => state.tableCalls.push(cmd),
  };
  const sandbox = {
    $j: Object.assign(function() {
      return fakeEl;
    }, {
      ajax: () => {
        const req = {
          abort: () => {},
          done: (fn) => {
            req.doneFn = fn;
            return req;
          },
          fail: (fn) => {
            req.failFn = fn;
            return req;
          },
        };
        state.req = req;
        return req;
      },
      each: (arr, fn) => (arr || []).forEach((v, i) => fn(i, v)),
    }),
    document: {},
    thisUrl: 'index.php',
    deferTableRequestWhileHidden: () => false,
    logAjaxFail: () => state.ajaxFails++,
    alert: (message) => state.alerts.push(message),
    console: console,
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

{
  const {sandbox, state} = load();
  sandbox.ajaxRequest({data: {}, success: () => {
    throw new Error('params.success must not be called for result=Error');
  }});
  state.req.doneFn({result: 'Error', message: 'Insufficient permissions'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.deepStrictEqual(state.alerts, ['Insufficient permissions']);
  console.log('ok - a result=Error answer stops the loading indicator');
}

{
  const {sandbox, state} = load();
  sandbox.ajaxRequest({data: {}});
  state.req.failFn({status: 500, statusText: 'Internal Server Error'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.ok(!state.tableCalls.includes('refresh'), 'refresh re-runs the failing request without end');
  assert.strictEqual(state.ajaxFails, 1);
  console.log('ok - a failed request stops the loading indicator without refreshing');
}
