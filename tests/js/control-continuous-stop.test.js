'use strict';

// Releasing a continuous zoom/focus/iris button must send that axis' stop
// command; controlCmd sent moveStop for every released button, so the lens kept
// moving. Usage: node control-continuous-stop.test.js <watch.js> <control.js>

const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function load(file) {
  const src = fs.readFileSync(file, 'utf8');
  const m = src.match(/^function controlCmd\(event\) \{[\s\S]*?^\}/m);
  assert.ok(m, 'controlCmd not found in ' + file);
  const sent = [];
  const ctx = {
    console: {log() {}},
    parseInt,
    streamMode: 'video',
    $j: () => ({offset: () => ({left: 0, top: 0}), width: () => 10, height: () => 10, val: () => '1'}),
    controlReq: (d) => sent.push(d.control),
  };
  vm.createContext(ctx);
  vm.runInContext(m[0] + '\n;this.controlCmd = controlCmd;', ctx);
  return {ctx, sent};
}

function press(file, value, type) {
  const {ctx, sent} = load(file);
  const button = {getAttribute: (n) => (n === 'value' ? value : null)};
  ctx.controlCmd({type, target: button, currentTarget: button, pageX: 0, pageY: 0});
  return sent[0];
}

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('ok   ' + name);
  } catch (e) {
    failed++;
    console.log('FAIL ' + name + ': ' + e.message);
  }
}

for (const file of process.argv.slice(2)) {
  console.log(file);
  test('pan release sends moveStop', () => assert.strictEqual(press(file, 'moveConLeft', 'mouseup'), 'moveStop'));
  test('zoom release sends zoomStop', () => assert.strictEqual(press(file, 'zoomConTele', 'mouseup'), 'zoomStop'));
  test('focus release sends focusStop', () => assert.strictEqual(press(file, 'focusConNear', 'mouseup'), 'focusStop'));
  test('iris release sends irisStop', () => assert.strictEqual(press(file, 'irisConOpen', 'mouseup'), 'irisStop'));
  test('zoom press sends the zoom command', () => assert.strictEqual(press(file, 'zoomConTele', 'mousedown'), 'zoomConTele'));
}
process.exit(failed ? 1 : 0);
