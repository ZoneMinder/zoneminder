'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/zone.js');
const src = fs.readFileSync(jsPath, 'utf8');

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

// Chainable catch-all: length 0 so table row code is skipped
function makeStub() {
  const stub = new Proxy(function() {}, {
    get: (t, p) => {
      if (p === 'length') return 1;
      if (p === 'css') return (...args) => (args.length > 1 ? stub : '0px');
      return () => stub;
    },
    apply: () => stub,
  });
  return stub;
}

function load() {
  const draggables = [];
  function pointEl() {
    const el = {};
    el.attr = () => el;
    el.css = () => el;
    el.mouseover = () => el;
    el.mouseout = () => el;
    el.draggable = (opts) => {
      draggables.push(opts);
      return el;
    };
    return el;
  }
  const $j = (arg) => (arg === '<div>' ? pointEl() : makeStub());
  $j.each = (arr, fn) => {
    for ( let i = 0; i < arr.length; i++ ) fn(i, arr[i]);
  };

  const poly = {
    points: {
      numberOfItems: 4,
      getItem: () => ({}),
      clear() {},
      appendItem() {},
    },
  };
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, isFinite, TypeError, Array, Object,
    $j, maxX: 100, maxY: 100,
    window: {addEventListener() {}, updateX() {}, updateY() {}},
    document: {
      querySelectorAll: () => [],
      createElement: () => ({}),
      getElementById: (id) => {
        if ( id == 'imageFeed1' ) return {clientWidth: 400, clientHeight: 300};
        if ( id == 'zonePoly' ) return poly;
        if ( /^newZone\[Points\]\[\d+\]\[[xy]\]$/.test(id) ) return {value: ''};
        return makeStub();
      },
    },
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.zone = {
    MonitorId: 1,
    Points: [{x: 10, y: 10}, {x: 90, y: 10}, {x: 90, y: 90}, {x: 10, y: 90}],
  };
  ctx.updateArea = function() {};
  ctx.checkDirty = function() {};
  return {ctx, draggables};
}

function setup() {
  const {ctx, draggables} = load();
  ctx.drawZonePoints();
  return {ctx, opts: draggables[2]};
}

console.log('zone drag edge');
test('containment is not set on point 2', () => {
  const {opts} = setup();
  assert.strictEqual(opts.containment, undefined);
});
test('dragging past the far edge clamps to the frame edge', () => {
  const {ctx, opts} = setup();
  const ui = {position: {left: 500, top: 400}};
  opts.drag({}, ui);
  assert.deepStrictEqual(
      {left: ui.position.left, top: ui.position.top}, {left: 400, top: 300});
  assert.strictEqual(ctx.zone.Points[2].x, 100);
  assert.strictEqual(ctx.zone.Points[2].y, 100);
});
test('dragging past the near edge clamps to zero', () => {
  const {ctx, opts} = setup();
  const ui = {position: {left: -5, top: -7}};
  opts.drag({}, ui);
  assert.deepStrictEqual(
      {left: ui.position.left, top: ui.position.top}, {left: 0, top: 0});
  assert.strictEqual(ctx.zone.Points[2].x, 0);
  assert.strictEqual(ctx.zone.Points[2].y, 0);
});
test('dragging to the middle gives 50/50', () => {
  const {ctx, opts} = setup();
  const ui = {position: {left: 200, top: 150}};
  opts.drag({}, ui);
  assert.strictEqual(ctx.zone.Points[2].x, 50);
  assert.strictEqual(ctx.zone.Points[2].y, 50);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
