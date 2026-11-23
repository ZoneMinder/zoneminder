'use strict';

// A cycle refresh time of 0 means off: neither cycleStart() nor initCycle()
// may start a setInterval poll. A positive time must still start one poll.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/cycle.js');
const src = fs.readFileSync(srcPath, 'utf8');

function load(cycleRefreshTimeout) {
  const intervals = [];
  const $j = () => ({
    val: () => 'auto', prop() {}, css() {}, width() {}, height() {}, ready() {},
  });
  const sandbox = {
    $j, document: {}, monitorData: [{id: 1, width: 640, height: 480}], monIdx: '0',
    mode: 'single', cycleRefreshTimeout, nextMid: '', console,
    setInterval: (fn, ms) => {
      intervals.push({fn, ms});
      return 1;
    },
    clearInterval() {},
    setCookie() {}, SCALE_BASE: 100,
    zmAuth: {appendTo: (u) => u},
    window: {location: {replace() {}}},
    MonitorStream: function() {
      this.setScale = () => {};
      this.start = () => {};
    },
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, intervals};
}

// refresh time 0: cycleStart() and initCycle() must not start a poll
{
  const {sandbox, intervals} = load(0);
  sandbox.cycleStart();
  assert.strictEqual(intervals.length, 0, 'cycleStart with refresh 0 must not start an interval');
}
{
  const {sandbox, intervals} = load(0);
  sandbox.initCycle();
  assert.strictEqual(intervals.length, 0, 'initCycle with refresh 0 must not start an interval');
}

// positive refresh time: exactly one poll with that delay
{
  const {sandbox, intervals} = load(10000);
  sandbox.cycleStart();
  assert.strictEqual(intervals.length, 1, 'cycleStart must start exactly one interval');
  assert.strictEqual(intervals[0].ms, 10000, 'cycleStart must use the refresh time as delay');
  assert.strictEqual(typeof intervals[0].fn, 'function', 'cycleStart must pass a function');
  assert.strictEqual(intervals[0].fn, sandbox.nextCycleView,
      'cycleStart must pass nextCycleView');
}
{
  const {sandbox, intervals} = load(10000);
  sandbox.initCycle();
  assert.strictEqual(intervals.length, 1, 'initCycle must start exactly one interval');
  assert.strictEqual(intervals[0].ms, 10000, 'initCycle must use the refresh time as delay');
  assert.strictEqual(typeof intervals[0].fn, 'function', 'initCycle must pass a function');
  assert.strictEqual(intervals[0].fn, sandbox.nextCycleView,
      'initCycle must pass nextCycleView');
}

console.log('ok');
