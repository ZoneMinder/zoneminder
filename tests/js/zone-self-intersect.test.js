'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/zone.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

function load(points) {
  const alerts = [];
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, Array, Object,
    $j: chain,
    alert: (m) => alerts.push(m),
    window: {addEventListener() {}},
    document: {getElementById: () => null, querySelectorAll: () => []},
    selfIntersectingString: 'SELF',
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.zone = {Points: points};
  ctx.alerts = alerts;
  return ctx;
}
const pts = (s) => s.split(' ').map((c) => ({x: c.split(',')[0], y: c.split(',')[1]}));
// An Inactive zone has no other fields to validate
const form = {elements: {'newZone[Type]': {value: 'Inactive'}}};

console.log('validateForm self intersection');
test('a square is accepted', () => {
  const ctx = load(pts('0,0 100,0 100,100 0,100'));
  assert.strictEqual(ctx.validateForm(form), true);
});
test('a bow tie is refused', () => {
  const ctx = load(pts('0,0 100,100 100,0 0,100'));
  assert.strictEqual(ctx.validateForm(form), false);
  assert.deepStrictEqual(ctx.alerts, ['SELF']);
});
test('a zone fixed after opening is accepted', () => {
  const ctx = load(pts('0,0 100,100 100,0 0,100'));
  ctx.zone.Points = pts('0,0 100,0 100,100 0,100');
  assert.strictEqual(ctx.validateForm(form), true);
});
test('a concave zone and a midpoint on an edge are accepted', () => {
  assert.strictEqual(load(pts('0,0 60,0 60,40 100,40 100,100 0,100')).validateForm(form), true);
  assert.strictEqual(load(pts('0,0 50,0 100,0 100,100 0,100')).validateForm(form), true);
});
test('a vertex touching another edge is refused', () => {
  assert.strictEqual(load(pts('0,0 100,0 100,100 50,0 0,100')).validateForm(form), false);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
