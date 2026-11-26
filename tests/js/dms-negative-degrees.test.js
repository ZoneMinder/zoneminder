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
vm.runInContext(dmsSrc + ';this.api = {parseDms}', ctx);
const {parseDms} = ctx.api;

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

function near(actual, expected) {
  assert.ok(Math.abs(actual - expected) < 1e-9, actual + ' != ' + expected);
}

test('negative degrees subtract the minutes and seconds', () => {
  near(parseDms('-79°56′55.903″'), -(79 + 56 / 60 + 55.903 / 3600));
  near(parseDms('-79:56:55'), -(79 + 56 / 60 + 55 / 3600));
});
test('negative degrees with decimal minutes', () => {
  near(parseDms('-79° 56.93172'), -(79 + 56.93172 / 60));
});
test('minus zero degrees keeps the sign', () => {
  near(parseDms('-0°30′0″'), -0.5);
});
test('a negative value with S or W is not flipped back', () => {
  near(parseDms('-33°52′7.68″ S'), -(33 + 52 / 60 + 7.68 / 3600));
});
test('positive and hemisphere forms are unchanged', () => {
  near(parseDms('40:26:46N'), 40 + 26 / 60 + 46 / 3600);
  near(parseDms('79:56:55W'), -(79 + 56 / 60 + 55 / 3600));
  near(parseDms('-79.948862'), -79.948862);
});
console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
