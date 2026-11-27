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
      numberOfItems: 8,
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
  ctx.updateAllPixelDisplays = function() {};
  return {ctx, draggables};
}

function points(ctx) {
  return ctx.zone.Points.map((p) => ({x: p.x, y: p.y}));
}

console.log('zone undo');

test('undo after addPoint restores the previous points', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  const before = points(ctx);
  ctx.addPoint(0);
  assert.strictEqual(ctx.zone.Points.length, 5);
  ctx.undo();
  assert.deepStrictEqual(points(ctx), before);
});

test('undo after updateX restores the old x', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  const input = {value: '30', getAttribute: () => '0'};
  ctx.updateX(input);
  assert.strictEqual(ctx.zone.Points[0].x, 30);
  ctx.undo();
  assert.strictEqual(ctx.zone.Points[0].x, 10);
});

test('typing one value into a point input is one undo step', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  const input = {value: '3', getAttribute: () => '0'};
  ctx.updateX(input);
  input.value = '35';
  ctx.updateX(input);
  input.value = '355';
  ctx.updateX(input);
  assert.strictEqual(ctx.undoStack.length, 1);
  ctx.undo();
  assert.strictEqual(ctx.zone.Points[0].x, 10);
});

test('a drag produces one undo step', () => {
  const {ctx, draggables} = load();
  ctx.drawZonePoints();
  const opts = draggables[0];
  opts.start({}, {});
  opts.drag({}, {position: {left: 100, top: 100}});
  opts.drag({}, {position: {left: 200, top: 150}});
  assert.strictEqual(ctx.zone.Points[0].x, 50);
  ctx.undo();
  assert.strictEqual(ctx.zone.Points[0].x, 10);
  assert.strictEqual(ctx.zone.Points[0].y, 10);
});

test('undo with an empty stack does nothing', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  const before = points(ctx);
  ctx.undo();
  assert.deepStrictEqual(points(ctx), before);
});

test('the undo stack is capped at 50', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  for ( let i = 0; i < 60; i++ ) {
    ctx.addPoint(0);
  }
  assert.strictEqual(ctx.undoStack.length, 50);
  for ( let i = 0; i < 51; i++ ) {
    ctx.undo();
  }
  // 4 original points plus the 10 that fell off the bottom of the stack
  assert.strictEqual(ctx.zone.Points.length, 14);
});

test('Ctrl+Z inside an input does not undo', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  ctx.addPoint(0);
  let prevented = false;
  const event = {
    key: 'z',
    ctrlKey: true,
    target: {tagName: 'INPUT'},
    preventDefault: () => {
      prevented = true;
    },
  };
  ctx.handleUndoKeydown(event);
  assert.strictEqual(ctx.zone.Points.length, 5);
  assert.strictEqual(prevented, false);
});

test('Ctrl+Z outside an input undoes', () => {
  const {ctx} = load();
  ctx.drawZonePoints = function() {};
  ctx.addPoint(0);
  let prevented = false;
  const event = {
    key: 'z',
    ctrlKey: true,
    target: {tagName: 'BODY'},
    preventDefault: () => {
      prevented = true;
    },
  };
  ctx.handleUndoKeydown(event);
  assert.strictEqual(ctx.zone.Points.length, 4);
  assert.strictEqual(prevented, true);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
