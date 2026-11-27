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
  const inputs = {};
  points.forEach((p, i) => {
    ['x', 'y'].forEach((axis) => {
      inputs['newZone[Points][' + i + '][' + axis + ']'] = {
        value: Number(p[axis]).toFixed(2),
        getAttribute: (name) => (name === 'data-point-index' ? String(i) : null),
      };
    });
  });

  // As in a browser, an SVGPoint refuses a non-finite coordinate
  const svgPoint = {};
  ['x', 'y'].forEach((k) => Object.defineProperty(svgPoint, k, {
    set: (v) => {
      if (!isFinite(v)) throw new TypeError('non-finite');
    },
  }));
  const poly = {
    points: {
      numberOfItems: points.length,
      getItem: () => svgPoint,
      clear() {},
      appendItem() {},
    },
  };

  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 1 : () => chain),
    apply: () => chain,
  });

  const listeners = {};
  const document = {
    addEventListener: (type, fn) => {
      (listeners[type] = listeners[type] || []).push(fn);
    },
    querySelector: () => null,
    querySelectorAll: () => [],
    getElementById: (id) => {
      if (id == 'imageFeed1') return {clientWidth: 400, clientHeight: 300};
      if (id == 'zonePoly') return poly;
      if (Object.prototype.hasOwnProperty.call(inputs, id)) return inputs[id];
      return null;
    },
  };

  const ctx = {
    console, Math, JSON, parseInt, parseFloat, isFinite, TypeError, Array, Object,
    $j: chain, maxX: 100, maxY: 100,
    window: {addEventListener() {}},
    document,
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.zone = {MonitorId: 1, Points: points.map((p) => ({x: p.x, y: p.y}))};
  ctx.updateArea = function() {};
  ctx.checkDirty = function() {};
  return {ctx, inputs, listeners};
}

function pointNode(index) {
  return {
    classList: {contains: (name) => name === 'zonePoint'},
    getAttribute: (name) => (name === 'data-point-index' ? String(index) : null),
    parentNode: null,
  };
}

function selectPoint(listeners, index) {
  (listeners.focusin || []).forEach((fn) => fn({target: pointNode(index)}));
}

function keydown(listeners, key, options) {
  const event = {
    key,
    shiftKey: !!(options && options.shiftKey),
    ctrlKey: !!(options && options.ctrlKey),
    altKey: !!(options && options.altKey),
    metaKey: !!(options && options.metaKey),
    target: (options && options.target) || {tagName: 'DIV'},
    defaultPrevented: false,
    preventDefault() {
      this.defaultPrevented = true;
    },
  };
  (listeners.keydown || []).forEach((fn) => fn(event));
  return event;
}

console.log('zone point keyboard nudge');
test('focusing a point selects it and ArrowRight moves it one unit', () => {
  const {ctx, inputs, listeners} = load([{x: 10, y: 20}, {x: 50, y: 20}]);
  selectPoint(listeners, 0);
  const event = keydown(listeners, 'ArrowRight');
  assert.strictEqual(ctx.zone.Points[0].x, 11);
  assert.strictEqual(ctx.zone.Points[0].y, 20);
  assert.strictEqual(inputs['newZone[Points][0][x]'].value, '11.00');
  assert.strictEqual(event.defaultPrevented, true);
});

test('Shift+ArrowDown moves the selected point ten units in y', () => {
  const {ctx, inputs, listeners} = load([{x: 10, y: 20}, {x: 50, y: 20}]);
  selectPoint(listeners, 0);
  keydown(listeners, 'ArrowDown', {shiftKey: true});
  assert.strictEqual(ctx.zone.Points[0].x, 10);
  assert.strictEqual(ctx.zone.Points[0].y, 30);
  assert.strictEqual(inputs['newZone[Points][0][y]'].value, '30.00');
});

test('repeated ArrowLeft clamps at the left edge', () => {
  const {ctx, listeners} = load([{x: 0, y: 10}, {x: 50, y: 20}]);
  selectPoint(listeners, 0);
  keydown(listeners, 'ArrowLeft');
  assert.strictEqual(ctx.zone.Points[0].x, 0);
});

test('Shift+ArrowRight clamps at the far edge', () => {
  const {ctx, inputs, listeners} = load([{x: 95, y: 10}, {x: 50, y: 20}]);
  selectPoint(listeners, 0);
  keydown(listeners, 'ArrowRight', {shiftKey: true});
  assert.strictEqual(ctx.zone.Points[0].x, 100);
  assert.strictEqual(inputs['newZone[Points][0][x]'].value, '100.00');
});

test('arrows do nothing when no point is selected', () => {
  const {ctx, inputs, listeners} = load([{x: 10, y: 20}, {x: 50, y: 20}]);
  const event = keydown(listeners, 'ArrowRight');
  assert.strictEqual(ctx.zone.Points[0].x, 10);
  assert.strictEqual(inputs['newZone[Points][0][x]'].value, '10.00');
  assert.strictEqual(event.defaultPrevented, false);
});

test('arrows are ignored while focus is in a text input', () => {
  const {ctx, listeners} = load([{x: 10, y: 20}, {x: 50, y: 20}]);
  selectPoint(listeners, 0);
  const event = keydown(listeners, 'ArrowRight', {target: {tagName: 'INPUT'}});
  assert.strictEqual(ctx.zone.Points[0].x, 10);
  assert.strictEqual(event.defaultPrevented, false);
});

test('arrows are ignored with Ctrl, Alt or Meta held', () => {
  ['ctrlKey', 'altKey', 'metaKey'].forEach((modifier) => {
    const {ctx, listeners} = load([{x: 10, y: 20}, {x: 50, y: 20}]);
    selectPoint(listeners, 0);
    const options = {target: {tagName: 'DIV'}};
    options[modifier] = true;
    keydown(listeners, 'ArrowRight', options);
    assert.strictEqual(ctx.zone.Points[0].x, 10, modifier);
  });
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
