'use strict';

// After the montage resizes monitors (ratio change, status position), monitors that moved
// into or out of the viewport must be started/stopped without waiting for a scroll.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const montagePath = process.argv[2] || process.env.MONTAGE_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/montage.js');
const src = fs.readFileSync(montagePath, 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function load() {
  const timers = [];
  const el = {offsetHeight: 100, getAttribute: () => 'false'};
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: () => ({on() {}}),
    ResizeObserver: class {
      observe() {}
    },
    document: {getElementById: () => el},
    setTimeout: (fn, ms) => timers.push({fn, ms}),
    clearTimeout() {},
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.objGridStack = {resizeToContent() {}};
  sandbox.setRatioForMonitor = () => {};
  sandbox.monitorsSetScale = () => {};
  sandbox.scrolls = 0;
  sandbox.on_scroll = () => {
    sandbox.scrolls++;
  };
  return {sandbox, timers};
}

test('a resized monitor triggers a visibility check', () => {
  const {sandbox, timers} = load();
  sandbox.changedMonitors.push(3);
  sandbox.processChangedMonitors();
  assert.strictEqual(sandbox.changedMonitors.length, 0);
  assert.strictEqual(timers.length, 1);
  timers[0].fn();
  assert.strictEqual(sandbox.scrolls, 1);
});

test('nothing changed, no visibility check', () => {
  const {sandbox, timers} = load();
  sandbox.processChangedMonitors();
  assert.strictEqual(timers.length, 0);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
