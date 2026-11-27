'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/zones.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

console.log('streamCmdQuit');
test('stops the streams that are running', () => {
  const stops = [];
  const running = [{id: 1}, {id: 2}].map((m) => ({stop: () => stops.push('running' + m.id)}));
  const ctx = {
    window: {addEventListener() {}},
    document: {},
    Array, clearTimeout, setTimeout,
    monitorData: [{id: 1}, {id: 2}],
    MonitorStream: function() {
      this.stop = () => stops.push('new');
    },
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.monitors = running;
  ctx.streamCmdQuit();
  assert.deepStrictEqual(stops, ['running1', 'running2']);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
