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

function load() {
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  // As in a browser, an SVGPoint refuses a non-finite coordinate
  const svgPoint = {};
  ['x', 'y'].forEach((k) => Object.defineProperty(svgPoint, k, {
    set: (v) => {
      if (!isFinite(v)) throw new TypeError('non-finite');
    },
  }));
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, isFinite, TypeError, Array, Object,
    $j: chain, maxX: 100, maxY: 100,
    window: {addEventListener() {}},
    document: {
      querySelectorAll: () => [],
      getElementById: (id) => (id == 'zonePoly' ?
        {points: {getItem: () => svgPoint}} : {clientWidth: 400, clientHeight: 300}),
    },
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.zone = {MonitorId: 1, Points: [{x: 10, y: 20}, {x: 50, y: 20}, {x: 50, y: 60}]};
  ctx.updateArea = function() {};
  return ctx;
}
const input = (value) => ({value: value, getAttribute: () => '0'});

console.log('point inputs');
test('an emptied X box leaves the point alone', () => {
  const ctx = load();
  ctx.updateX(input(''));
  assert.strictEqual(ctx.getCoordString(), '10.00,20.00 50.00,20.00 50.00,60.00');
});
test('an emptied Y box leaves the point alone', () => {
  const ctx = load();
  ctx.updateY(input(''));
  assert.strictEqual(ctx.getCoordString(), '10.00,20.00 50.00,20.00 50.00,60.00');
});
test('a typed value still moves the point', () => {
  const ctx = load();
  ctx.updateX(input('30'));
  ctx.updateY(input('40'));
  assert.strictEqual(ctx.getCoordString(), '30.00,40.00 50.00,20.00 50.00,60.00');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
