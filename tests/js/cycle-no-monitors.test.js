'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(path.join(__dirname, '../../web/skins/classic/views/js/cycle.js'), 'utf8');

function load(monitorData) {
  const calls = {stream: 0, replace: 0};
  const $j = () => ({
    val: () => '', prop() {}, css() {}, width() {}, height() {},
    ready() {},
  });
  const sandbox = {
    $j, document: {}, monitorData, monIdx: '0', mode: 'single', cycleRefreshTimeout: 1000,
    nextMid: '', console, setInterval: () => 1, clearInterval() {},
    zmAuth: {appendTo: (u) => u},
    window: {location: {replace() {
      calls.replace++;
    }}},
    MonitorStream: function() {
      calls.stream++;
      this.setScale = () => {};
      this.start = () => {};
    },
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, calls};
}

const {sandbox, calls} = load([]);
sandbox.initCycle();
sandbox.cycleNext();
sandbox.cyclePrev();
assert.strictEqual(calls.stream, 0);
assert.strictEqual(calls.replace, 0);
console.log('ok cycle with no monitors does not throw');
