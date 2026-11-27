'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/snapshots.js'), 'utf8');

const requests = [];
let refreshes = 0;
const fakeEl = {
  prop: () => {},
  on: () => {},
  ready: () => {},
  bootstrapTable: (cmd) => {
    if (cmd == 'refresh') refreshes++;
  },
};
const sandbox = {
  $j: Object.assign(function() {
    return fakeEl;
  }, {
    ajax: () => {
      const req = {
        abort: () => req.failFn({statusText: 'abort'}),
        done: (fn) => {
          req.doneFn = fn;
          return req;
        },
        fail: (fn) => {
          req.failFn = fn;
          return req;
        },
      };
      requests.push(req);
      return req;
    },
  }),
  document: {},
  thisUrl: 'index.php',
  deferTableRequestWhileHidden: () => false,
  logAjaxFail: () => {},
  console: console,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

sandbox.ajaxRequest({data: {}});
sandbox.ajaxRequest({data: {}});

assert.strictEqual(refreshes, 0, 'aborting the superseded request must not refresh the table');
assert.strictEqual(requests.length, 2);
console.log('ok - aborted snapshot request does not trigger a refresh');
