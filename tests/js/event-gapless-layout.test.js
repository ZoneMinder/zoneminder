'use strict';

// Gapless playback loads a new event page for every event, and each load used to
// resize the video several times and finish with a page-wide wipe:
//
//  - video.js set up the player on DOMContentLoaded with no size, and the
//    first changeScale() only ran on window load, so a collapsed video (and
//    the controls drawn up under it) could be painted first, and the size was
//    computed from that collapsed layout.
//  - skin.js's page-leave roll-up animated the content to nothing on every
//    reload, which between events is a wipe from the bottom up.
//
// event.js now sizes the video on DOMContentLoaded, remembers the size per
// monitor so event.php opens the next page at it, and moves between events
// through goToEvent(), which turns the roll-up off for that reload.

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

// Load event.js with the globals it uses stubbed. `player` is what
// videojs.getPlayer('videoobj') returns: a player for a video.js page, null
// for a zms one.
function loadEventJs(player) {
  const chain = makeChainable();
  const cookies = {};
  const listeners = {};
  const globals = {
    $j: (sel) => {
      if (sel === '#scale' || sel === '#streamQuality') return {val: () => '0'};
      return chain;
    },
    $: chain,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: (type, fn) => {
        (listeners[type] = listeners[type] || []).push(fn);
      },
      createElement: () => ({}),
    },
    location: {replaced: null, replace(url) {
      this.replaced = url;
    }},
    videojs: {getPlayer: () => player},
    scaleToFit: () => ({width: 1174, height: 660, autoScale: 90}),
    setButtonSizeOnStream: () => {},
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: (name, value) => {
      cookies[name] = value;
    },
    rates: [],
    eventData: {MonitorId: 3, Width: 1280, Height: 720},
    thisUrl: '?',
    filterQuery: '&fq',
    sortQuery: '&sq',
    skipPageLeaveAnimation: false,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  // Laying out the stats panel needs a real window; not what these tests are about.
  globals.onStatsResize = () => {};
  return {globals, cookies, listeners};
}

console.log('moving between events');

test('goToEvent() turns the page-leave roll-up off and loads the event', () => {
  const {globals} = loadEventJs(null);
  globals.goToEvent(42);
  assert.strictEqual(globals.skipPageLeaveAnimation, true);
  assert.strictEqual(globals.location.replaced, '??view=event&eid=42&fq&sq');
});

console.log('sizing the video before the first paint');

test('a video.js page is sized on DOMContentLoaded', () => {
  const player = {};
  const {globals, cookies, listeners} = loadEventJs(player);
  assert.ok(listeners.DOMContentLoaded && listeners.DOMContentLoaded.length,
      'event.js registers no DOMContentLoaded handler');
  listeners.DOMContentLoaded.forEach((fn) => fn());
  assert.strictEqual(globals.vid, player, 'the handler should use the existing player');
  assert.strictEqual(cookies.zmEventVideoSize3, '1174x660',
      'the size should be remembered for the next page of this monitor');
});

test('a zms page is left to initPage()', () => {
  const {globals, cookies, listeners} = loadEventJs(null);
  (listeners.DOMContentLoaded || []).forEach((fn) => fn());
  assert.strictEqual(globals.vid, null);
  assert.strictEqual(cookies.zmEventVideoSize3, undefined);
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
