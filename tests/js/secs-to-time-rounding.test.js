'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

function extract(startMarker, endMarker) {
  const start = src.indexOf(startMarker);
  assert.ok(start >= 0, 'missing ' + startMarker);
  const end = src.indexOf(endMarker, start);
  return src.slice(start, end + endMarker.length);
}

const ctx = {};
vm.createContext(ctx);
vm.runInContext(extract('function formatSeconds', '\n}\n'), ctx);
vm.runInContext(extract('function secsToTime', '\n}\n'), ctx);

const t = (s, r) => ctx.secsToTime(s, r);

// Unrounded and plain cases keep their old output.
assert.strictEqual(t(5), '5');
assert.strictEqual(t(65), '1:05');
assert.strictEqual(t(3725), '1:02:05');
assert.strictEqual(t(12.34, 1), '12.3');
assert.strictEqual(t(65.25, 1), '1:05.3');

// Rounding that reaches the next unit carries instead of printing 60 seconds.
assert.strictEqual(t(59.96, 1), '1:00.0');
assert.strictEqual(t(119.97, 1), '2:00.0');
assert.strictEqual(t(69.996, 2), '1:10.00');
assert.strictEqual(t(3599.96, 1), '1:00:00.0');
assert.strictEqual(t(3659.97, 1), '1:01:00.0');

console.log('secs-to-time-rounding: ok');
