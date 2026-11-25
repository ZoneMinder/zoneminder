'use strict';

// The event view's details table (getStat() in event.js) reads Frames,
// AlarmFrames and the Score fields (TotScore, MaxScore, AvgScore) from the
// event row. Those columns can be null for an event whose analysis never ran,
// so a raw interpolation printed "null" (or "undefined") in the Frames and
// Score cells. getStat() falls back to 0 for a missing value instead.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = path.resolve(
    process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/js/event.js'));
const src = fs.readFileSync(srcPath, 'utf8');
const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
// The shipped escapeHTML(), not a copy of it
const escapeSrc = skin.match(/^function escapeHTML\(text\) \{[\s\S]*?^\}/m)[0];

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

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

// getStat() builds a <th> with .text() and a <td> with .html(); record what
// each cell is given, keyed by the row's label.
function loadEventJs(eventData) {
  const cells = {};
  let label = null;
  function element(tag) {
    const el = {
      addClass: () => el,
      text(t) {
        if (tag === 'th') label = t;
        return el;
      },
      html(h) {
        if (tag === 'td') cells[label] = h;
        return el;
      },
      append: () => el,
      empty: () => el,
    };
    return el;
  }
  const $j = function(sel) {
    const m = typeof sel === 'string' && sel.match(/^<(\w+)/);
    return m ? element(m[1]) : makeChainable();
  };
  $j.each = (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k]));

  const globals = {
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0, clearTimeout: () => 0, setInterval: () => 0, clearInterval: () => 0,
    getCookie: () => null, setCookie: () => {},
    eventData: eventData,
    eventDataStrings: {
      Name: 'Name', Frames: 'Frames', Score: 'Score',
    },
    canView: {Monitors: false},
    translate: {},
    isEmpty: (o) => !o || Object.keys(o).length === 0,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(escapeSrc, sandbox, {filename: 'skin.js'});
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  assert.strictEqual(typeof globals.getStat, 'function', 'event.js should define getStat()');
  return {getStat: globals.getStat, cells};
}

console.log('event details table with null statistics');
test('null Frames and Score values are shown as 0, not null or undefined', () => {
  const {getStat, cells} = loadEventJs({
    Id: '7', Name: 'E', MonitorId: '1',
    Frames: null, AlarmFrames: null, TotScore: null, MaxScore: null, AvgScore: null,
  });
  getStat();
  assert.ok(!/null/.test(cells['Frames']), 'Frames should not contain "null": ' + cells['Frames']);
  assert.ok(!/undefined/.test(cells['Frames']),
      'Frames should not contain "undefined": ' + cells['Frames']);
  assert.ok(!/null/.test(cells['Score']), 'Score should not contain "null": ' + cells['Score']);
  assert.ok(!/undefined/.test(cells['Score']),
      'Score should not contain "undefined": ' + cells['Score']);
  assert.ok(cells['Frames'].includes('>0</a>'),
      'Frames should show 0: ' + cells['Frames']);
});
test('populated Frames and Score values render unchanged', () => {
  const {getStat, cells} = loadEventJs({
    Id: '7', Name: 'E', MonitorId: '1',
    Frames: '12', AlarmFrames: '3', TotScore: '40', MaxScore: '9', AvgScore: '4',
  });
  getStat();
  assert.ok(cells['Frames'].includes('>12</a>'),
      'Frames should show 12: ' + cells['Frames']);
  assert.ok(cells['Frames'].includes('Alarm:<a href="?view=frames&amp;eid=7">3</a>'),
      'Frames should show the alarm frames: ' + cells['Frames']);
  assert.ok(cells['Score'].includes('Total:40'),
      'Score should show the total: ' + cells['Score']);
  assert.ok(cells['Score'].includes('Max:9'),
      'Score should show the max: ' + cells['Score']);
  assert.ok(cells['Score'].includes('Avg:4'),
      'Score should show the average: ' + cells['Score']);
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
