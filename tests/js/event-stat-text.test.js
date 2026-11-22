'use strict';

// The event view's details table (getStat() in event.js) built each cell as an
// HTML string and set it with .html(), so an event's Name, Cause or Notes that
// held markup was rendered as markup instead of shown as its text. The values
// come from the event row (renamed in the UI, set by the API or a detection
// tool), so they are escaped with skin.js's escapeHTML() before they join the
// cell's own markup (the Frames and Score links, the monitor buttons).

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');
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
      Name: 'Name', MonitorName: 'Monitor', Cause: 'Cause', Notes: 'Notes',
      DiskSpace: 'Disk Space', Path: 'Path',
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

const MARKUP = '<b id="x">bold</b> & "q" \'s\'';
const ESCAPED = '&lt;b id=&quot;x&quot;&gt;bold&lt;/b&gt; &amp; &quot;q&quot; &#39;s&#39;';

console.log('event details table');
test('Name, Cause and Notes are shown as text', () => {
  const {getStat, cells} = loadEventJs({
    Id: '7', Name: MARKUP, MonitorId: '1', MonitorName: 'Front', Cause: MARKUP, Notes: MARKUP,
  });
  getStat();
  assert.strictEqual(cells['Name'], ESCAPED + ' (Id:7)');
  assert.strictEqual(cells['Cause'], ESCAPED);
  assert.strictEqual(cells['Notes'], ESCAPED);
});
test('the monitor name is shown as text', () => {
  const {getStat, cells} = loadEventJs({Id: '7', MonitorId: '1', MonitorName: MARKUP});
  getStat();
  assert.strictEqual(cells['Monitor'], ESCAPED);
});
test('storage name and path are shown as text, the path link encoded', () => {
  const {getStat, cells} = loadEventJs({
    Id: '7', MonitorId: '1', DiskSpace: '1.2 MB', Storage: MARKUP, Path: '/var/a&b/<1>',
  });
  getStat();
  assert.strictEqual(cells['Disk Space'], '1.2 MB on ' + ESCAPED);
  assert.strictEqual(cells['Path'],
      '<a href="?view=files&amp;path=%2Fvar%2Fa%26b%2F%3C1%3E">/var/a&amp;b/&lt;1&gt;</a>');
});
test('plain values are unchanged', () => {
  const {getStat, cells} = loadEventJs({
    Id: '7', Name: 'Event 7', MonitorId: '1', Cause: 'Motion', Notes: 'Motion: All',
  });
  getStat();
  assert.strictEqual(cells['Name'], 'Event 7 (Id:7)');
  assert.strictEqual(cells['Cause'], 'Motion');
  assert.strictEqual(cells['Notes'], 'Motion: All');
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
