'use strict';

// ll2dms() converts the Location tab's Latitude/Longitude to degrees, minutes and
// seconds. It needs both values, but only filled the DMS box of the field that
// changed, so entering Longitude first and then Latitude left the Longitude DMS
// box empty. Both boxes are now filled once both values are there.
//
// Run: node tests/js/monitor-location-dms.test.js [path/to/monitor.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
const srcPath = process.argv[2] ||
    path.join(root, 'web/skins/classic/views/js/monitor.js');
const src = fs.readFileSync(srcPath, 'utf8');

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

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  let depth = 0;
  for (let i = src.indexOf('{', start); ; i++) {
    if (src[i] === '{') depth++;
    else if (src[i] === '}' && --depth === 0) return src.slice(start, i + 1);
  }
}

function run(lat, lon, changed) {
  const els = {
    'newMonitor[Latitude]': {id: 'newMonitor[Latitude]', value: lat},
    'newMonitor[Longitude]': {id: 'newMonitor[Longitude]', value: lon},
    'LatitudeDMS': {value: ''},
    'LongitudeDMS': {value: ''},
  };
  const ctx = {
    document: {getElementById: (id) => els[id]},
    console: {log: () => {}},
    updateMarker: () => {},
    // Stand-in for the DMS library: the conversion itself isn't under test.
    DmsCoordinates: function(la, lo) {
      this.latitude = {toString: () => 'lat ' + la};
      this.longitude = {toString: () => 'lon ' + lo};
    },
  };
  vm.createContext(ctx);
  vm.runInContext(extract('ll2dms'), ctx);
  ctx.ll2dms(els[changed]);
  return els;
}

console.log('monitor.js ll2dms');

test('entering Latitude after Longitude fills both DMS boxes', () => {
  const els = run('51.5', '-0.12', 'newMonitor[Latitude]');
  assert.strictEqual(els.LatitudeDMS.value, 'lat 51.5');
  assert.strictEqual(els.LongitudeDMS.value, 'lon -0.12');
});

test('entering Longitude after Latitude fills both DMS boxes', () => {
  const els = run('51.5', '-0.12', 'newMonitor[Longitude]');
  assert.strictEqual(els.LatitudeDMS.value, 'lat 51.5');
  assert.strictEqual(els.LongitudeDMS.value, 'lon -0.12');
});

test('nothing is filled while one coordinate is still empty', () => {
  const els = run('', '-0.12', 'newMonitor[Longitude]');
  assert.strictEqual(els.LatitudeDMS.value, '');
  assert.strictEqual(els.LongitudeDMS.value, '');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
