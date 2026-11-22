'use strict';

// streamQuery() clears the status poll interval once zmsBroke is set. When the
// stream is restarted (Play/seek after zms died) the interval has to be armed
// again, otherwise the progress bar, time/fps readouts and next/prev lookups
// stay frozen even though the video plays.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.EVENT_JS || path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(srcPath, 'utf8');

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
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

// Load event.js with a stream image whose src is streamSrc, and return the
// globals, the image and the recorded setInterval calls.
function loadEventJs(streamSrc) {
  const img = {src: streamSrc, onload: null};
  const intervals = [];
  const globals = {
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: (id) => (id === 'evtStream' ? img : null),
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: (fn, ms) => {
      intervals.push({fn, ms});
      return intervals.length;
    },
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    eventData: {},
    currentScale: 100,
    URL: URL,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  const run = (code) => vm.runInContext(code, sandbox);
  return {globals, img, intervals, run};
}

const STREAM = 'http://zm.example/cgi-bin/nph-zms?source=event&event=10&frame=5&mode=jpeg&scale=50&connkey=123';

test('re-arms the status poll after a zms restart', () => {
  const {img, intervals, run} = loadEventJs(STREAM);
  assert.strictEqual(run('vid'), null);
  // These are top-level let/var bindings in event.js, so set them in the context.
  run('zmsBroke = true; streamTimeout = 2000; zmsHints = {reset() {}};');
  intervals.length = 0;
  run('restartZmsStream()');
  assert.strictEqual(typeof img.onload, 'function');
  img.onload();
  assert.strictEqual(run('zmsBroke'), false);
  assert.strictEqual(intervals.length, 1);
  assert.strictEqual(intervals[0].fn, run('streamQuery'));
  assert.strictEqual(intervals[0].ms, 2000);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
