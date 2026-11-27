'use strict';

// cycleStart() took a fresh setInterval id without clearing the one already in
// cycleIntervalId. Several callers reach it with no cyclePause() in between -
// the play button, the are-you-still-watching modal closing, and startPage(),
// which runs on visibilitychange, resume and pageshow, more than one of which
// fires on a single restore. The overwritten id is unrecoverable, so the orphan
// interval keeps ticking and cyclePause() can only ever stop the last one.
// refs #5135

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

// Chains like jQuery, but val() answers with a number so cycleStart()'s
// `secondsToCycle == 0` has something real to compare against.
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

// watch.js is a browser file that leans on globals from skin.js and the view
// template. Resolve unknown globals to undefined, and give real values only to
// the timer functions, which are what these tests measure.
function loadWatch() {
  const timers = {live: new Set(), nextId: 1, cleared: []};
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
    setInterval: () => {
      const id = timers.nextId++;
      timers.live.add(id);
      return id;
    },
    clearInterval: (id) => {
      timers.cleared.push(id);
      timers.live.delete(id);
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    Object: Object,
    Array: Array,
    JSON: JSON,
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
  assert.strictEqual(typeof globals.cycleStart, 'function',
      'watch.js did not define cycleStart');
  return {globals, timers};
}

console.log('watch.js cycle interval lifecycle');

test('the hazard is real: an overwritten interval id cannot be cleared', () => {
  // What the bug was, independent of watch.js: take a second id into the same
  // variable and the first interval is running with nobody holding its handle.
  const live = new Set();
  let next = 1;
  const set = () => {
    const id = next++; live.add(id); return id;
  };
  const clear = (id) => live.delete(id);
  let handle = set();
  handle = set();
  clear(handle);
  assert.strictEqual(live.size, 1, 'the orphaned interval should still be live');
});

test('calling cycleStart twice leaves exactly one interval running', () => {
  const {globals, timers} = loadWatch();
  globals.cycleStart();
  assert.strictEqual(timers.live.size, 1, 'first start should arm one interval');
  globals.cycleStart();
  assert.strictEqual(timers.live.size, 1,
      'second start left ' + timers.live.size + ' intervals running');
});

test('cyclePause after repeated starts leaves nothing running', () => {
  const {globals, timers} = loadWatch();
  globals.cycleStart();
  globals.cycleStart();
  globals.cycleStart();
  globals.cyclePause();
  assert.strictEqual(timers.live.size, 0,
      'cyclePause could not stop every interval cycleStart armed');
});

test('startPage clears prevStateCycle so a second restore does not re-arm', () => {
  // A restore fires more than one of visibilitychange/resume/pageshow, so
  // startPage() runs twice. prevStateStarted is nulled on the way through;
  // prevStateCycle has to be too, or the second run starts cycling again.
  const {globals, timers} = loadWatch();
  // Run the auth gate synchronously; it is not what this test is about.
  globals.whenAuthFresh = (cb) => cb();
  globals.prevStateCycle = true;

  globals.startPage();
  assert.strictEqual(timers.live.size, 1, 'first restore should arm one interval');
  assert.strictEqual(globals.prevStateCycle, null,
      'prevStateCycle should be cleared once acted on');

  globals.startPage();
  assert.strictEqual(timers.live.size, 1,
      'second restore left ' + timers.live.size + ' intervals running');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
