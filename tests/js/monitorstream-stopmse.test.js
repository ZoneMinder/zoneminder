'use strict';

// stopMse() waited for a SourceBuffer updateend that never comes when the
// MediaSource already has no source buffers, so the promise stayed pending and
// the restart chained on it never ran.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');
const start = src.indexOf('  this.stopMse = function');
const end = src.indexOf('  this.kill = function', start);
assert.ok(start >= 0 && end > start);

const ctx = {console: {warn() {}}, dateTimeToISOLocal: () => ''};
vm.createContext(ctx);
vm.runInContext('function M() {\n' + src.slice(start, end) + '\n}\nthis.M = M;', ctx);

const m = new ctx.M();
m.id = 1;
m.closeWebSocket = () => {};
m.mseSourceBuffer = {updating: false, removeEventListener() {}, addEventListener() {}, remove() {
  throw new Error('must not be called with no source buffers');
}};
m.mse = {sourceBuffers: [], removeEventListener() {}, removeSourceBuffer() {}, endOfStream() {}};

let timer;
const timeout = new Promise((_, rej) => {
  timer = setTimeout(() => rej(new Error('stopMse() never resolved')), 500);
});
Promise.race([m.stopMse(), timeout]).then(() => {
  clearTimeout(timer);
  assert.strictEqual(m.mseSourceBuffer, null);
  assert.strictEqual(m.mse, null);
  console.log('ok');
}).catch((e) => {
  console.error(e.message);
  process.exit(1);
});
