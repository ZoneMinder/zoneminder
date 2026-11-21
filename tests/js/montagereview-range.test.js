'use strict';

// Montage review: moving the end date before the start date made
// changeFilters() set the start equal to the end and compute
// rangeTimeSecs = 0. Every time-to-x conversion divides by the range, so the
// timeline, the playhead and click-to-seek all turned into NaN. The server
// emits the range inclusive (max - min + 1), so changeFilters must as well.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js'), 'utf8');

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

function dt(ms) {
  return {
    isValid: true,
    valueOf: function() {
      return ms;
    },
    plus: function(o) {
      return dt(ms + ((o.hours || 0) * 3600 + (o.days || 0) * 86400) * 1000);
    },
    diff: function(other) {
      const d = ms - other.valueOf();
      return {toObject: function() {
        return {seconds: d / 1000, milliseconds: d};
      }};
    },
    toFormat: function() {
      return new Date(ms).toISOString();
    },
  };
}

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name + '\n    ' + e.message);
    failed++;
  }
}

function run(minValue, maxValue, which) {
  const minEl = {value: minValue};
  const maxEl = {value: maxValue};
  const ctx = {
    minStartDateTimeElement: minEl, maxStartDateTimeElement: maxEl,
    minTimeSecs: 0, maxTimeSecs: 0, rangeTimeSecs: 0, currentTimeSecs: 0,
    minTime: '', maxTime: '', events: {}, events_for_monitor: {},
    LOADING: false, timerObj: null,
    inputstr2dt: function(v) {
      return dt(Date.parse(v.replace(' ', 'T') + 'Z'));
    },
    clearInterval: function() {}, drawGraph: function() {}, loadEventData: function() {},
    wait_for_events: function() {}, console: {warn: function() {}},
  };
  vm.createContext(ctx);
  vm.runInContext(extract('changeFilters'), ctx);
  ctx.changeFilters.call(which === 'max' ? maxEl : minEl);
  return ctx;
}

test('end moved before the start leaves a non-zero range', function() {
  const ctx = run('2024-01-01 10:00:00', '2024-01-01 09:00:00', 'max');
  assert.ok(ctx.rangeTimeSecs > 0, 'rangeTimeSecs is ' + ctx.rangeTimeSecs);
  assert.strictEqual(ctx.rangeTimeSecs, ctx.maxTimeSecs - ctx.minTimeSecs + 1);
});

test('range is inclusive like the value the server emits', function() {
  const ctx = run('2024-01-01 09:00:00', '2024-01-01 10:00:00', 'max');
  assert.strictEqual(ctx.rangeTimeSecs, 3601);
});

if (failed) process.exit(1);
console.log('all passed');
