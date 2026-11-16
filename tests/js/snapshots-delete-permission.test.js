'use strict';

// The snapshots list gated its delete button and handler on Events edit, but
// the server (ajax/snapshots.php task=delete) requires Snapshots edit. A user
// with Snapshots edit but no Events edit could not delete from the list, and a
// user with Events edit only got a button that the server then refused.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/snapshots.js'), 'utf8');

function run(canEdit) {
  const calls = [];
  const props = [];
  let denied = 0;
  const fakeEl = {
    prop: (k, v) => props.push([k, v]),
    on: () => {},
    ready: () => {},
    bootstrapTable: (cmd) => (cmd == 'getSelections' ? [{Id: '<a href="x">7</a>'}] : undefined),
    show: () => {},
  };
  const $j = Object.assign(function() {
    return fakeEl;
  }, {
    getJSON: (url) => {
      calls.push(url);
      const p = {done: () => p, fail: () => p};
      return p;
    },
    map: (arr, fn) => arr.map(fn),
  });
  const sandbox = {
    $j, document: {}, thisUrl: 'index.php', canEdit, console,
    enoperm: () => denied++,
    // The page strips the link markup from the Id column; the test only has this one row.
    strip_html: (s) => (s == '<a href="x">7</a>' ? '7' : s),
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.manageDelConfirmModalBtns();
  return {calls, denied};
}

let r = run({Events: false, Snapshots: true});
assert.strictEqual(r.denied, 0, 'Snapshots edit must be enough to delete from the list');
assert.strictEqual(r.calls.length, 1);
assert.ok(/ids\[\]=7/.test(r.calls[0]));

r = run({Events: true, Snapshots: false});
assert.strictEqual(r.calls.length, 0, 'Events edit alone must not send a delete the server refuses');
assert.strictEqual(r.denied, 1);

assert.ok(!/canEdit\.Events/.test(src), 'no remaining Events permission checks in the snapshots list');
console.log('ok - snapshots list delete follows Snapshots edit');
