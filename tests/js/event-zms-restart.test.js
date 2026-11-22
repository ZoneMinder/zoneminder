'use strict';

// When zms stops answering, the event view restarts it by reloading the stream
// image. zms moves from event to event by itself, following the page's filter
// across monitors (refs #4186), so by then it can be well past the event the
// page was opened on. Reloading the original URL started playback over from
// that first event.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');

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
// globals and the image.
function loadEventJs(streamSrc) {
  const img = {src: streamSrc, onload: null};
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
    setInterval: () => 0,
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
  return {globals, img};
}

const STREAM = 'http://zm.example/cgi-bin/nph-zms?source=event&event=10&frame=5&mode=jpeg&scale=50&connkey=123';

test('restarts on the event zms had moved on to', () => {
  const {globals, img} = loadEventJs(STREAM);
  globals.lastEventId = 13;
  globals.restartZmsStream();
  const url = new URL(img.src);
  assert.strictEqual(url.searchParams.get('event'), '13');
  // The frame was a position in event 10.
  assert.strictEqual(url.searchParams.get('frame'), null);
  assert.strictEqual(url.searchParams.get('connkey'), '123');
  assert.strictEqual(url.searchParams.get('scale'), '100');
});

test('keeps the frame when zms is still on the event it opened', () => {
  const {globals, img} = loadEventJs(STREAM);
  globals.lastEventId = 10;
  globals.restartZmsStream();
  const url = new URL(img.src);
  assert.strictEqual(url.searchParams.get('event'), '10');
  assert.strictEqual(url.searchParams.get('frame'), '5');
});

test('keeps the URL before zms has reported any event', () => {
  const {globals, img} = loadEventJs(STREAM);
  globals.restartZmsStream();
  const url = new URL(img.src);
  assert.strictEqual(url.searchParams.get('event'), '10');
  assert.strictEqual(url.searchParams.get('frame'), '5');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
