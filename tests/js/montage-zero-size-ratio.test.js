'use strict';

// montage.js's setRatioForMonitor() derives an element height from the
// monitor's width/height ratio. A monitor row whose width or height has not
// been filled in yet (0) made the ratio NaN or Infinity, and the resulting
// "NaNpx"/"Infinitypx"/"0px" was written into the stream's inline style. The
// function now checks that the computed height is a positive finite number
// before touching the DOM.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = path.resolve(
    process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/js/montage.js'));
const src = fs.readFileSync(srcPath, 'utf8');
const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

// Pull a top-level function (and only that function) out of the shipped file
// so the test runs the real implementation, not a copy of it.
function extractFunction(source, name, file) {
  const re = new RegExp('^function ' + name + '\\([^)]*\\) \\{[\\s\\S]*?^\\}', 'm');
  const match = source.match(re);
  assert.ok(match, `${file} should define ${name}()`);
  return match[0];
}

const setRatioSrc = extractFunction(src, 'setRatioForMonitor', 'montage.js');
const getSelectedSrc = extractFunction(src, 'getSelected', 'montage.js');
const stringToNumberSrc = extractFunction(skin, 'stringToNumber', 'skin.js');

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

function makeSelect(value) {
  return {selectedIndex: 0, options: [{value: value}]};
}

let ratioSelect = makeSelect('real');
const imageFeed = {getAttribute: () => null};

const sandbox = {
  console: {log() {}, debug() {}, warn() {}, error() {}},
  document: {
    getElementById(id) {
      if (id === 'ratio5') return ratioSelect;
      if (id === 'imageFeed5') return imageFeed;
      return null;
    },
  },
  monitors: [],
  averageMonitorsRatio: 1,
};
vm.createContext(sandbox);
vm.runInContext(getSelectedSrc, sandbox, {filename: 'montage.js'});
vm.runInContext(stringToNumberSrc, sandbox, {filename: 'skin.js'});
vm.runInContext(setRatioSrc, sandbox, {filename: 'montage.js'});
assert.strictEqual(typeof sandbox.setRatioForMonitor, 'function',
    'montage.js should define setRatioForMonitor()');

// Run setRatioForMonitor for monitor id 5 with the given dimensions and the
// ratio select value; return the stream element it may have styled.
function run(width, height, value) {
  ratioSelect = makeSelect(value);
  sandbox.monitors = [{id: 5, width: width, height: height}];
  const objStream = {
    id: 'liveStream5',
    clientWidth: 400,
    naturalHeight: 100,
    style: {},
    parentNode: {style: {}},
  };
  sandbox.objStream = objStream;
  sandbox.setRatioForMonitor(objStream);
  return objStream;
}

function assertHeightUnset(style, where) {
  assert.strictEqual(style.height, undefined,
      `${where} should not be written, got ${JSON.stringify(style.height)}`);
  assert.ok(!/^(NaN|Infinity|-?0)px$/.test(String(style.height)),
      `${where} should not be NaNpx/Infinitypx/0px`);
}

function assertHeight(style, expected, where) {
  assert.strictEqual(style.height, expected,
      `${where} should be ${expected}, got ${JSON.stringify(style.height)}`);
}

console.log('montage ratio with incomplete monitor dimensions');

test('0x0 monitor with "real" ratio leaves both heights unset', () => {
  const stream = run(0, 0, 'real');
  assertHeightUnset(stream.style, 'stream style.height');
  assertHeightUnset(stream.parentNode.style, 'parentNode style.height');
});

test('0x480 monitor with "real" ratio leaves both heights unset', () => {
  const stream = run(0, 480, 'real');
  assertHeightUnset(stream.style, 'stream style.height');
  assertHeightUnset(stream.parentNode.style, 'parentNode style.height');
});

test('640x0 monitor with "real" ratio leaves both heights unset', () => {
  const stream = run(640, 0, 'real');
  assertHeightUnset(stream.style, 'stream style.height');
  assertHeightUnset(stream.parentNode.style, 'parentNode style.height');
});

test('640x480 monitor with "real" ratio gets 300px in both heights', () => {
  const stream = run(640, 480, 'real');
  assertHeight(stream.style, '300px', 'stream style.height');
  assertHeight(stream.parentNode.style, '300px', 'parentNode style.height');
});

test('640x480 monitor with "4:3" ratio gets 300px in both heights', () => {
  const stream = run(640, 480, '4:3');
  assertHeight(stream.style, '300px', 'stream style.height');
  assertHeight(stream.parentNode.style, '300px', 'parentNode style.height');
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
