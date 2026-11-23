'use strict';

// When there is a gap between the end of one event and the start of the next,
// vjsReplay drops a countdown over the player and starts an interval that ticks
// it down. The interval used to be kept in a local (`const x`), so pressing
// Next during the gap left it running: the old countdown kept updating the
// overlay and eventually called streamNext again. The timer has to live in a
// scope that streamNext can clear, and starting a new countdown has to clear
// the previous one.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.argv[2] ? path.resolve(process.argv[2])
      : path.resolve(__dirname, '../../web/skins/classic/views/js/event.js'),
    'utf8'
);

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

// Slices a single function out of event.js. The file ends these functions with
// a column-0 `}`, sometimes followed by a comment (`} // end function ...`), so
// stop at the closing brace rather than at the end of that line.
function sliceFunction(name) {
  const match = src.match(
      new RegExp('function ' + name + '\\([^)]*\\) \\{[\\s\\S]*?\\n\\}')
  );
  assert.ok(match, 'function ' + name + ' not found in event.js');
  return match[0];
}

const chainable = {
  remove() {
    return chainable;
  },
  append() {
    return chainable;
  },
  height() {
    return 100;
  },
  html() {
    return chainable;
  },
  prop() {
    return chainable;
  },
};

// A fresh vm per test so interval bookkeeping does not leak between cases.
function loadEventJs() {
  const intervals = [];
  let nextId = 1;
  const calls = {
    goToEvent: 0, eventQuery: 0, streamReq: 0, streamPlay: 0, pauseClicked: 0,
  };

  const sandbox = {
    $j: () => chainable,
    console: {log() {}, error() {}, warn() {}},
    vid: {pause() {}, play() {}},
    replayMode: {value: 'all'},
    nextEventId: 2,
    eventData: {EndDateTime: '2026-01-01 10:00:00'},
    nextEventStartTime: Date.parse('2026-01-01 10:00:30'),
    formatCountdown: () => '00:00:30',
    pauseClicked() {
      calls.pauseClicked++;
    },
    goToEvent() {
      calls.goToEvent++;
    },
    eventQuery() {
      calls.eventQuery++;
    },
    streamReq() {
      calls.streamReq++;
    },
    streamPlay() {
      calls.streamPlay++;
    },
    // Records every interval so a test can see whether it was cleaned up.
    setInterval(fn, ms) {
      const record = {fn, id: nextId++, ms, cleared: false};
      intervals.push(record);
      return record.id;
    },
    clearInterval(id) {
      if (id === undefined || id === null) return;
      const record = intervals.find((r) => r.id === id);
      if (record) record.cleared = true;
    },
  };

  vm.createContext(sandbox);

  let code = sliceFunction('vjsReplay') + '\n' + sliceFunction('streamNext');
  // The original file kept the timer in a local, so it has no top-level
  // declaration for the test to hang the interval on. Only declare it when the
  // file under test actually uses the shared name.
  if (src.includes('gapCountdownTimer')) {
    code = 'var gapCountdownTimer = null;\n' + code;
  }
  vm.runInContext(code, sandbox, {filename: 'event.js'});

  return {sandbox, intervals, calls};
}

console.log('pressing Next during the gap');

test('clears the countdown vjsReplay started', () => {
  const env = loadEventJs();
  env.sandbox.vjsReplay();
  assert.strictEqual(env.intervals.length, 1,
      'vjsReplay should start exactly one countdown');
  assert.strictEqual(env.intervals[0].cleared, false,
      'the countdown should be running after vjsReplay');

  // The user presses Next while the countdown is showing.
  env.sandbox.streamNext(true);

  assert.strictEqual(env.calls.goToEvent, 1,
      'streamNext should have moved to the next event');
  assert.strictEqual(env.intervals[0].cleared, true,
      'pressing Next should clear the gap countdown');
});

console.log('replaying a second time');

test('clears the previous countdown before starting another', () => {
  const env = loadEventJs();
  env.sandbox.vjsReplay();
  env.sandbox.vjsReplay();

  assert.strictEqual(env.intervals.length, 2, 'two countdowns were started');
  assert.strictEqual(env.intervals[0].cleared, true,
      'the earlier countdown should be cleared');
  assert.strictEqual(env.intervals[1].cleared, false,
      'the latest countdown should keep running');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
