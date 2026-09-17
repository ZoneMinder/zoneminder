'use strict';

// Clicking fast-forward once too many at 16x threw out of video.js:
//
//   TypeError: HTMLMediaElement.playbackRate setter: Value being assigned is
//   not a finite floating-point value.
//     ... playbackRate@video.min.js
//         streamFastFwd@..._views_js_event-....js
//
// streamFastFwd stepped the rate list by indexing it directly,
// rates[rates.indexOf(current) + 1], which is undefined at the top of the
// list; undefined/100 is NaN and Firefox refuses a non-finite playbackRate.
// The guard that disables the button ran after the assignment, and
// streamPlay() re-enables the button at any rate, so the state that throws is
// reachable in normal use. streamFastRev had the same fault at the other end,
// where rates[-1] made revSpeed NaN and fed currentTime a NaN every tick.

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

function loadEventJs() {
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
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    rates: RATES,
    eventData: {},
  };
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
  assert.strictEqual(typeof globals.stepRate, 'function',
      'event.js did not define stepRate');
  return globals;
}

const ZM = loadEventJs();
const stepRate = ZM.stepRate;

console.log('stepping forward');

test('steps to the next rate up', () => {
  assert.strictEqual(stepRate(RATES, 100, 1), 200);
  assert.strictEqual(stepRate(RATES, 200, 1), 500);
  assert.strictEqual(stepRate(RATES, 1000, 1), 1600);
});

test('the top rate has nowhere to go', () => {
  // This is the crash: the old code produced rates[15], undefined, and
  // handed playbackRate NaN.
  assert.strictEqual(stepRate(RATES, 1600, 1), null);
});

test('stepping up through zero does not stall', () => {
  assert.strictEqual(stepRate(RATES, -25, 1), 0);
  assert.strictEqual(stepRate(RATES, 0, 1), 25);
});

console.log('stepping back');

test('steps to the next rate down', () => {
  assert.strictEqual(stepRate(RATES, -50, -1), -100);
  assert.strictEqual(stepRate(RATES, -1000, -1), -1600);
});

test('the bottom rate has nowhere to go', () => {
  // The reverse equivalent: rates[-1] made revSpeed NaN.
  assert.strictEqual(stepRate(RATES, -1600, -1), null);
});

console.log('rates that are not in the list');

test('snaps to the nearest listed rate rather than to index -1', () => {
  // indexOf answers -1, and the old -1 + 1 indexed rates[0], so stepping
  // *forward* from an unlisted rate jumped to full reverse.
  assert.strictEqual(stepRate(RATES, 300, 1), 500);
  assert.strictEqual(stepRate(RATES, 300, -1), 100);
});

test('a rate a hair off a listed one steps as though it were that one', () => {
  assert.strictEqual(stepRate(RATES, 99.9999, 1), 200);
  assert.strictEqual(stepRate(RATES, 100.0001, 1), 200);
});

test('a rate exactly between two snaps to the lower of them', () => {
  // 75 is equidistant from 50 and 100. Either would be defensible; this pins
  // which one happens so it is a decision rather than an accident.
  assert.strictEqual(stepRate(RATES, 75, 1), 100);
  assert.strictEqual(stepRate(RATES, 75, -1), 25);
});

test('a rate beyond the top of the list still cannot step past it', () => {
  assert.strictEqual(stepRate(RATES, 5000, 1), null);
  assert.strictEqual(stepRate(RATES, 5000, -1), 1000);
});

console.log('rates the player cannot answer for');

test('a non-finite current rate steps nowhere instead of guessing', () => {
  // A player that has not started can answer with undefined, and
  // undefined*100 is NaN. The old code turned that into rates[0], full
  // reverse; doing nothing lets the user click again once it is ready.
  assert.strictEqual(stepRate(RATES, NaN, 1), null);
  assert.strictEqual(stepRate(RATES, Infinity, 1), null);
  assert.strictEqual(stepRate(RATES, undefined, 1), null);
  assert.strictEqual(stepRate(RATES, null, 1), null);
  assert.strictEqual(stepRate(RATES, '100', 1), null);
});

test('an empty or missing rate list steps nowhere', () => {
  assert.strictEqual(stepRate([], 100, 1), null);
  assert.strictEqual(stepRate(undefined, 100, 1), null);
});

console.log('nothing the caller can pass yields a bad rate');

test('every reachable result is a finite number or null', () => {
  // The whole point: playbackRate(next/100) must never see NaN. Sweep every
  // listed rate, several unlisted ones, and the junk a player can answer
  // with, in both directions.
  const inputs = RATES.concat(
      [-5000, -333, -1, 1, 37, 300, 1599, 1601, 5000, 0.5],
      [NaN, Infinity, -Infinity, undefined, null, '100', {}, []]);
  for (const direction of [1, -1]) {
    for (const current of inputs) {
      const next = stepRate(RATES, current, direction);
      if (next === null) continue;
      assert.ok(typeof next === 'number' && isFinite(next),
          'stepRate(' + String(current) + ', ' + direction + ') gave ' + String(next));
      assert.ok(RATES.indexOf(next) >= 0,
          'stepRate(' + String(current) + ', ' + direction + ') left the list: ' + next);
    }
  }
});

test('walking the list end to end terminates at both ends', () => {
  // A full sweep the way a user holding the button down would do it. If a
  // step ever produced something unlisted this would not terminate on the
  // value it should.
  let current = RATES[0];
  let steps = 0;
  while (stepRate(RATES, current, 1) !== null) {
    current = stepRate(RATES, current, 1);
    assert.ok(++steps <= RATES.length, 'forward walk did not terminate');
  }
  assert.strictEqual(current, 1600, 'forward walk should end at the top rate');
  assert.strictEqual(steps, RATES.length - 1);

  steps = 0;
  while (stepRate(RATES, current, -1) !== null) {
    current = stepRate(RATES, current, -1);
    assert.ok(++steps <= RATES.length, 'backward walk did not terminate');
  }
  assert.strictEqual(current, -1600, 'backward walk should end at the bottom rate');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
