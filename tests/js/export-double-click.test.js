'use strict';

// Clicking Export twice before the first request returned started a second
// export and overwrote exportTimer, so the first interval was never cleared
// and kept rewriting the ticker after the export had finished. After a
// failure the errorText class also stayed on the progress box for the retry.
// Usage: node export-double-click.test.js [path/to/export.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/export.js'), 'utf8');

let ajaxCalls = 0;
const active = new Set();
let nextTimer = 1;
const classes = new Set();
let ticker = '';

function chainable() {
  const obj = {
    addClass: (c) => {
      c.split(' ').forEach((x) => classes.add(x)); return obj;
    },
    removeClass: (c) => {
      c.split(' ').forEach((x) => classes.delete(x)); return obj;
    },
    text: (t) => {
      if (t !== undefined) ticker = t; return obj;
    },
    append: () => obj,
    serialize: () => '',
  };
  return obj;
}
const $j = () => chainable();
$j.ajax = () => {
  ajaxCalls++; return chainable();
};

const sandbox = {
  console: {log() {}, warn() {}, error() {}},
  $j: $j,
  thisUrl: 'index.php',
  exportProgressString: 'Exporting',
  exportSucceededString: 'Done',
  setInterval: () => {
    const id = nextTimer++; active.add(id); return id;
  },
  clearInterval: (id) => active.delete(id),
  setTimeout: () => 1,
  logAjaxFail: () => {},
  document: {getElementById: () => ({addEventListener: () => {}}), referrer: ''},
  window: {addEventListener: () => {}, location: {replace() {}}, history: {back() {}}},
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'export.js'});

sandbox.exportEvents();
sandbox.exportEvents();
assert.strictEqual(ajaxCalls, 1, 'a second click must not start a second export');

sandbox.exportResponse({result: 'Ok', exportFile: 'a'}, '');
assert.strictEqual(active.size, 0, 'no progress timer may keep running after the export finished');

sandbox.exportEvents();
sandbox.exportFail({}, 'error', 'x');
assert.strictEqual(active.size, 0, 'no progress timer may keep running after a failure');
assert(classes.has('errorText'), 'failure is marked');
sandbox.exportEvents();
assert.strictEqual(ajaxCalls, 3, 'export can be retried after a failure');
assert(!classes.has('errorText'), 'a retry clears the previous error mark');
console.log('ok - export double click guarded, timers cleared');
