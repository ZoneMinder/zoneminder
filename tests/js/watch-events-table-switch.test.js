'use strict';

// Opening the watch page on a WebSite monitor used to leave the events table
// out of the markup, so moving on to a camera never showed one. The table is
// now always rendered and streamReStart() shows or hides it per monitor type.
//
// Usage: node tests/js/watch-events-table-switch.test.js [path/to/watch.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

const src = fs.readFileSync(
    process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js'),
    'utf8');

function restart(newType) {
  const events = {display: 'unset'};
  const chain = makeChainable();
  const $j = new Proxy(function() {}, {
    get: (t, p) => chain[p],
    apply: (t, thisArg, args) => {
      if (args[0] === '#events') {
        return {
          css: (prop, val) => {
            events[prop] = val;
          },
        };
      }
      return chain;
    },
  });
  const imageFeed = {removeEventListener: () => {}};
  const title = {};
  const globals = {
    $j,
    $: chain,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: (id) => {
        if (id === 'monitor') return {classList: {add() {}}, innerHTML: ''};
        if (id.startsWith('nav-item-cycle')) {
          return {querySelector: () => ({classList: {add() {}, remove() {}}})};
        }
        return null;
      },
      querySelector: (sel) => (sel === 'title' ? title : imageFeed),
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
      location: {href: 'http://localhost/zm/index.php?view=watch&mid=1'},
    },
    history: {pushState: () => {}},
    zmPanZoom: {action: () => {}, init: () => {}},
    setInterval: () => 1, clearInterval: () => {},
    setTimeout: () => 0, clearTimeout: () => 0,
    Object, Array, JSON, Set, parseInt, parseFloat, String, Number, URL,
    ZM_WEB_VIEWING_TIMEOUT: 0,
    addEventListener: () => {},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  globals.eventListTable = {bootstrapTable: () => {}};
  globals.monitorStream = false;
  globals.monitorData = [
    {id: 1, name: 'One', streamHTML: '', monitorType: newType === 'WebSite' ? 'Ffmpeg' : 'WebSite'},
    {id: 2, name: 'Two', streamHTML: '', monitorType: newType},
  ];
  globals.controlWhatDisplay = () => {};
  globals.applyMonitorControllable = () => {};
  globals.streamPrepareStart = () => {};
  globals.observeStreamElements = () => {};
  globals.streamReStart(1, 2);
  return events.display;
}

console.log('watch.js events table follows the monitor type');

test('moving to a camera shows the events table', () => {
  assert.strictEqual(restart('Ffmpeg'), '');
});

test('moving to a WebSite monitor hides the events table', () => {
  assert.strictEqual(restart('WebSite'), 'none');
});

done();
