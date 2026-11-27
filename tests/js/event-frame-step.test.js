'use strict';

// Frame stepping divided Length by Frames once, at page load:
//
//   var spf = Math.round((eventData.Length / eventData.Frames)*1000000 )/1000000;
//
// An event that is still recording has no frame count yet: Frames is '' (or
// '0'). ''/undefined is NaN and a positive length over 0 is Infinity, and
// streamSlowFwd/streamSlowRev then called vid.currentTime(currentTime +/- spf),
// so a single click on frame step broke the player's seek position. The step
// has to be computed when it is used, and has to fall back when the event does
// not have a usable frame count.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.resolve(__dirname, process.argv[2] ||
        '../../web/skins/classic/views/js/event.js'), 'utf8');

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

// The rate list skins/classic/includes/config.php defines, which is what
// event.js.php hands the page as `rates`.
const RATES = [-1600, -1000, -500, -200, -100, -50, -25, 0, 25, 50, 100, 200, 500, 1000, 1600];

// event.js is a browser file that leans on globals from skin.js and the view
// templates. Resolve any unknown global to undefined so it can be evaluated;
// only what these tests touch is given a real value.
function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

function loadEventJs(overrides) {
  const globals = Object.assign({
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
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    rates: RATES,
    eventData: {},
  }, overrides || {});
  const sandbox = new Proxy(globals, {
    // has:true makes every bare identifier resolve here, so the built-ins
    // event.js uses (Math, Date, JSON) have to be handed back explicitly or
    // they come out undefined.
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

// Enough of the videojs player for frame stepping: a currentTime that can be
// read and written, recording into state.time.
function makePlayer(state) {
  return {
    currentTime: function(time) {
      if (time === undefined) return state.time;
      state.time = time;
      return undefined;
    },
  };
}

// Loads a fresh copy of event.js for an event with the given Length/Frames,
// wired to a stand-in player whose clock starts at startTime.
function withPlayer(eventData, startTime) {
  const state = {time: startTime === undefined ? 10 : startTime};
  const env = loadEventJs({eventData: eventData});
  // event.js declares `var vid = null` at load; this is the browser's later
  // assignment, which the sandbox proxy resolves back to globals.vid.
  env.vid = makePlayer(state);
  return {state: state, env: env};
}

console.log('an event that is still recording');

test('stepping forward moves a frame, not to Infinity', () => {
  // Frames is '' while recording: the old spf was NaN.
  const player = withPlayer({Length: '60', Frames: ''}, 10);
  player.env.streamSlowFwd(true);
  assert.ok(isFinite(player.state.time),
      'currentTime became ' + player.state.time);
  assert.ok(player.state.time > 10, 'time did not move forward');
  assert.ok(player.state.time < 11, 'time jumped too far: ' + player.state.time);
});

test('stepping back moves a frame, not to NaN', () => {
  const player = withPlayer({Length: '60', Frames: ''}, 10);
  player.env.streamSlowRev(true);
  assert.ok(isFinite(player.state.time),
      'currentTime became ' + player.state.time);
  assert.ok(player.state.time < 10, 'time did not move back');
  assert.ok(player.state.time > 9, 'time jumped too far: ' + player.state.time);
});

console.log('an event with a frame count');

test('one frame is Length divided by Frames', () => {
  // 60 seconds over 1500 frames is 0.04s per frame.
  const player = withPlayer({Length: '60', Frames: '1500'}, 10);
  player.env.streamSlowFwd(true);
  assert.ok(Math.abs(player.state.time - 10.04) < 1e-9,
      'expected 10.04, got ' + player.state.time);
});

console.log('an event with a zero frame count');

test('Frames of 0 falls back rather than dividing by zero', () => {
  const player = withPlayer({Length: '60', Frames: '0'}, 10);
  player.env.streamSlowFwd(true);
  assert.ok(isFinite(player.state.time),
      'currentTime became ' + player.state.time);
  assert.ok(Math.abs(player.state.time - 10.04) < 1e-9,
      'expected the 25 fps fallback, got ' + player.state.time);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
