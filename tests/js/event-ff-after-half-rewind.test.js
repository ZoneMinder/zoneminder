'use strict';

// Picking -1/2x in the rate select and then clicking fast-forward on the video
// player left the rewind running. changeRate stores revSpeed = 0.5 for -50, and
// 0.5 is also the "not rewinding" value stopRewind() resets to, so
// streamFastFwd's `revSpeed != .5` check took a live -1/2x rewind for no rewind
// and did not stop it. The interval then set playbackRate back to 0 and moved
// currentTime back every 500ms while the select showed a forward rate.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] || process.env.EVENT_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(file, 'utf8');

const RATES = [-1600, -1000, -500, -200, -100, -50, -25, 0, 25, 50, 100, 200, 500, 1000, 1600];

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

function load(state) {
  const jq = function(selector) {
    if (typeof selector === 'string' && selector.indexOf('name="rate"') >= 0) {
      return {
        val: function(value) {
          if (value === undefined) return state.select;
          state.select = value;
          return this;
        },
      };
    }
    return makeChainable();
  };
  const globals = {
    $j: jq,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: (fn) => {
      state.interval = fn;
      return 42;
    },
    clearInterval: () => {
      state.interval = null;
    },
    getCookie: () => null,
    setCookie: () => {},
    setButtonState: () => {},
    rates: RATES,
    eventData: {},
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
  return globals;
}

const state = {select: '-50', rate: 1, time: 100, interval: null};
const env = load(state);
env.vid = {
  playbackRate: (rate) => {
    if (rate === undefined) return state.rate;
    state.rate = rate;
    return undefined;
  },
  currentTime: (time) => {
    if (time === undefined) return state.time;
    state.time = time;
    return undefined;
  },
  pause: () => {},
};

env.changeRate();
assert.ok(state.interval, 'no rewind interval after picking -1/2x');
state.interval(); // one tick: the player is now at playbackRate 0, time 99.75
assert.strictEqual(state.rate, 0);

env.streamFastFwd();

assert.strictEqual(state.interval, null, 'rewind interval survived fast-forward');
assert.ok(state.rate > 0, 'player is not playing forwards: rate ' + state.rate);
const before = state.time;
if (state.interval) state.interval();
assert.strictEqual(state.time, before, 'time still moved backwards');
console.log('ok fast-forward after a -1/2x rewind stops the rewind');
