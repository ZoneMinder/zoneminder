'use strict';

// Montage review's pan buttons move the time window by half its width.
// click_panleft moved the playhead (currentTimeSecs) with the window, but
// click_panright did not. clicknav only passes the current time on when it
// lies inside the new range, so panning right from a playhead in the first
// half of the window dropped it and the view restarted at the new minimum,
// while panning left kept the same relative position.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js'), 'utf8');

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  assert.ok(start >= 0, name + ' not found');
  let depth = 0;
  for (let i = src.indexOf('{', start); i < src.length; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && --depth === 0) return src.slice(start, i + 1);
  }
  throw new Error('unterminated ' + name);
}

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

function run(fnName, state) {
  const ctx = Object.assign({calls: []}, state);
  ctx.clicknav = function(min, max, live) {
    ctx.calls.push({min: min, max: max, live: live, current: ctx.currentTimeSecs});
  };
  vm.createContext(ctx);
  vm.runInContext(extract(fnName) + '\n' + fnName + '();', ctx);
  return ctx;
}

// A one hour window with the playhead 10 minutes in.
const STATE = {minTimeSecs: 1000000, maxTimeSecs: 1003599, rangeTimeSecs: 3600, currentTimeSecs: 1000600};

test('pan left moves the playhead with the window', function() {
  const ctx = run('click_panleft', STATE);
  const c = ctx.calls[0];
  assert.strictEqual(c.min, 998200);
  assert.strictEqual(c.current - c.min, STATE.currentTimeSecs - STATE.minTimeSecs);
});

test('pan right moves the playhead with the window', function() {
  const ctx = run('click_panright', STATE);
  const c = ctx.calls[0];
  assert.strictEqual(c.min, 1001800);
  assert.strictEqual(c.current - c.min, STATE.currentTimeSecs - STATE.minTimeSecs);
});

test('pan right keeps the playhead inside the new range', function() {
  const ctx = run('click_panright', STATE);
  const c = ctx.calls[0];
  assert.ok(c.current > c.min && c.current < c.max,
      'current ' + c.current + ' outside ' + c.min + '..' + c.max);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
