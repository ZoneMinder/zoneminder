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

function loadZone(points) {
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, Array, Object,
    $j: chain,
    window: {addEventListener() {}},
    document: {getElementById: () => null, querySelectorAll: () => []},
    monitorArea: 10000,
    monitorPixelArea: 640 * 480,
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.zone = {MonitorId: 1, Points: points};
  // Needs the DOM; the points are what matters here.
  ctx.drawZonePoints = function() {};
  ctx.updateAllPixelDisplays = function() {};
  ctx.checkDirty = function() {};
  return ctx;
}

console.log('delPoint');
test('area follows the polygon after a point is deleted', () => {
  const ctx = loadZone([{x: 0, y: 0}, {x: 100, y: 0}, {x: 100, y: 100},
    {x: 0, y: 100}, {x: 0, y: 50}]);
  ctx.zone.Area = ctx.Polygon_calcArea(ctx.zone.Points);
  assert.strictEqual(ctx.zone.Area, 10000);
  ctx.delPoint(1);
  assert.strictEqual(ctx.zone.Area, 5000);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
