'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/events.js'), 'utf8');

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
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
}

function location(lat, lng) {
  const ctx = {
    $j: {each: (arr, fn) => arr.forEach((v, i) => fn(i, v))},
    canEdit: {}, yesString: 'Yes', archivedString: '', emailedString: '',
    filterQuery: '', sortQuery: '', Date, String,
  };
  vm.createContext(ctx);
  vm.runInContext(extract('processRows') + ';this.processRows = processRows', ctx);
  const row = {
    Id: 1, Archived: 'No', Emailed: 'No', Name: 'n', Monitor: 'm', Cause: 'c', Notes: '',
    Frames: 1, AlarmFrames: 0, MaxScore: 0, DiskSpace: '', Storage: '', Length: 5,
    Latitude: lat, Longitude: lng,
  };
  try {
    ctx.processRows([row]);
  } catch (e) {
    if (row.Location === undefined) throw e;
  }
  return row.Location;
}

test('an event without coordinates shows an empty location', () => {
  assert.strictEqual(location(null, null), '');
});

test('an event of a monitor without a location shows an empty location', () => {
  assert.strictEqual(location('0.00000000', '0.00000000'), '');
});

test('an event with coordinates shows them', () => {
  assert.strictEqual(location('48.13700000', '11.57500000'), '48.13700000, 11.57500000');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
