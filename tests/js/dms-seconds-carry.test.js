'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
const dmsSrc = fs.readFileSync(path.join(root, 'web/js/dms.js'), 'utf8')
    .replace('export default class', 'class')
    .replace('export function', 'function')
    .replace('export class', 'class');

const ctx = {};
vm.createContext(ctx);
vm.runInContext(dmsSrc + ';this.api = {Dms, DmsCoordinates, parseDms}', ctx);
const {Dms} = ctx.api;

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

test('10.2 degrees is 10 12 0, not 10 11 60', () => {
  assert.strictEqual(new Dms(10.2, 'lat').toString(2), '10°12′0.00″ N');
});
test('seconds that round up to 60 carry into the minute', () => {
  assert.strictEqual(new Dms(12.999999999, 'lat').toString(2), '13°0′0.00″ N');
});
test('carry past 59 minutes reaches the degree', () => {
  assert.strictEqual(new Dms(59.99999999999, 'lon').toString(2), '60°0′0.00″ E');
});
test('negative values keep the hemisphere', () => {
  assert.strictEqual(new Dms(-10.2, 'lon').toString(2), '10°12′0.00″ W');
});
test('ordinary value is unchanged', () => {
  assert.strictEqual(new Dms(-33.8688, 'lat').toString(2), '33°52′7.68″ S');
});
test('dmsArray never reports 60 seconds', () => {
  for (const v of [10.2, 20.4, 33.7, 40.1, 0.1, 5.3, 77.7]) {
    const a = new Dms(v, 'lat').dmsArray;
    assert.ok(a[2] < 60 && a[1] < 60, v + ' -> ' + a);
  }
});
console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
