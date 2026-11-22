'use strict';

// Typing a new Max Image Buffer Count on the Buffers tab fires an input event
// per keystroke. buffer_setting_oninput() cut PreEventCount down to the
// maximum whenever it was larger, so typing 50 over 121 passed through 5 and
// left PreEventCount at 5 instead of 10. The maximum is now checked on change.
//
// Run: node tests/js/monitor-max-buffer-typing.test.js [path to monitor.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js');
const src = fs.readFileSync(file, 'utf8');
const start = src.indexOf('function buffer_setting_oninput(e) {');
const end = src.indexOf('function update_estimated_ram_use() {');
assert(start > 0 && end > start, 'buffer_setting_oninput not found');

const max = {id: 'newMonitor[MaxImageBufferCount]', value: '121'};
const pre = {id: 'newMonitor[PreEventCount]', value: '10'};
const ctx = {
  document: {getElementById: (id) => (id == max.id ? max : pre)},
  update_estimated_ram_use: () => {},
};
vm.createContext(ctx);
vm.runInContext(src.substring(start, end) + '; this.fn = buffer_setting_oninput;', ctx);

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name + '\n    ' + e.message);
    failed++;
  }
}

test('typing a new maximum leaves PreEventCount alone', () => {
  max.value = '121'; pre.value = '10';
  for (const v of ['5', '50']) {
    max.value = v;
    ctx.fn.call(max, {type: 'input'});
  }
  assert.strictEqual(pre.value, '10');
});

test('committing a maximum below PreEventCount lowers PreEventCount', () => {
  max.value = '4'; pre.value = '10';
  ctx.fn.call(max, {type: 'change'});
  assert.strictEqual(pre.value, '4');
});

test('raising PreEventCount past the maximum raises the maximum', () => {
  max.value = '20'; pre.value = '30';
  ctx.fn.call(pre, {type: 'input'});
  assert.strictEqual(max.value, '30');
});

process.exit(failed ? 1 : 0);
