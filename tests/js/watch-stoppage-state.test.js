'use strict';

// stopPage() runs from the hidden-tab timer, 'freeze' and 'pagehide', so it can
// run twice for one hide. The second run found the stream already stopped and
// the cycle already paused, and overwrote prevStateStarted with 'stopped' and
// prevStateCycle with false, so startPage() restored neither.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js'), 'utf8');

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

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => {
      if (prop === 'then') return undefined;
      if (prop === 'val') return () => '10';
      if (prop === 'text') return () => '';
      return proxy;
    },
    apply: () => proxy,
  });
  return proxy;
}

function loadWatch() {
  const globals = {
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setInterval: () => 1,
    clearInterval: () => {},
    setTimeout: () => 0,
    clearTimeout: () => 0,
    Object, Array, JSON,
    monitorData: [{id: 1}, {id: 2}],
    ZM_WEB_VIEWING_TIMEOUT: 0,
    addEventListener: () => {},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  globals.whenAuthFresh = (cb) => cb();
  globals.monitorsSetScale = () => {};
  globals.onPlay = () => {};
  const ms = {
    started: true, zmsState: null, element: {}, isActive: true,
    currentChannelStream: 'default', starts: 0,
    stop() {
      this.started = false;
    },
    start() {
      this.starts++;
      this.started = true;
    },
  };
  globals.monitorStream = ms;
  return {globals, ms};
}

console.log('watch.js stopPage/startPage');

test('a second stopPage keeps the state startPage restores', () => {
  const {globals, ms} = loadWatch();
  globals.cycle = true;
  globals.stopPage();
  globals.stopPage();
  globals.startPage();
  assert.strictEqual(ms.starts, 1, 'stream was not restarted');
  assert.strictEqual(globals.cycle, true, 'cycle was not resumed');
});

test('stopPage after a restore stops again', () => {
  const {globals, ms} = loadWatch();
  globals.stopPage();
  globals.startPage();
  assert.strictEqual(ms.starts, 1);
  globals.stopPage();
  assert.strictEqual(ms.started, false);
  globals.startPage();
  assert.strictEqual(ms.starts, 2);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
