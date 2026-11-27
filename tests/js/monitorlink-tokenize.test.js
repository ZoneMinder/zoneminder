'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorLinkExpression.js'), 'utf8');
const ctx = vm.createContext({console: console});
vm.runInContext(src, ctx);

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

function tok(expr) {
  const out = vm.runInContext(
      'tokenize(' + JSON.stringify(expr) + ').map((t) => t.type + ":" + t.value)', ctx);
  return Array.from(out);
}

test('plain or expression', () => {
  assert.deepStrictEqual(tok('1|2:3'), ['link:1', '|:|', 'link:2:3']);
});

test('single parentheses', () => {
  assert.deepStrictEqual(tok('(1|2)&3'),
      ['(:(', 'link:1', '|:|', 'link:2', '):)', '&:&', 'link:3']);
});

test('repeated parentheses are one token', () => {
  assert.deepStrictEqual(tok('((1|2)&3)'),
      ['(:((', 'link:1', '|:|', 'link:2', '):)', '&:&', 'link:3', '):)']);
  assert.deepStrictEqual(tok('(1&(2|3))'),
      ['(:(', 'link:1', '&:&', '(:(', 'link:2', '|:|', 'link:3', '):))']);
});

test('empty expression gives no tokens', () => {
  assert.deepStrictEqual(tok(''), []);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
