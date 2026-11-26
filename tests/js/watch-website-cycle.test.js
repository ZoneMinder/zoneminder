'use strict';

// A WebSite monitor has no video stream, so the watch page's cycle used to
// stall on it: nextCycleView() only counted down once the AV stream reached
// readyState 2. It also read monitorStream.fatalError when monitorStream was
// false (opened directly on a WebSite monitor), and streamReStart() called
// monitorStream.destroyVolumeSlider() in the same situation. Both threw.
//
// WATCH_SRC lets this run against another copy of watch.js, e.g. the original:
//   WATCH_SRC=/tmp/watch-orig.js node tests/js/watch-website-cycle.test.js

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

const src = fs.readFileSync(
    process.env.WATCH_SRC ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js'),
    'utf8');

// Same loader as watch-sandbox.js, but reading the source selected above.
function loadSource(overrides = {}) {
  const globals = Object.assign({
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setInterval: () => 1,
    clearInterval: () => {},
    setTimeout: () => 0,
    clearTimeout: () => 0,
    Object, Array, JSON, Set, parseInt, parseFloat, String, Number,
    URL,
    monitorData: [{id: 1}, {id: 2}],
    ZM_WEB_VIEWING_TIMEOUT: 0,
    addEventListener: () => {},
  }, overrides);
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  return globals;
}

console.log('watch.js cycling away from a WebSite monitor');

test('a WebSite monitor counts down and cycles without a stream', () => {
  const g = loadSource();
  g.currentMonitor = {monitorType: 'WebSite'};
  g.monitorStream = false;
  g.secondsToCycle = 2;
  let cycled = 0;
  g.cycleNext = () => {
    cycled++;
  };

  g.nextCycleView();
  g.nextCycleView();

  assert.strictEqual(cycled, 1, 'the cycle never advanced past the WebSite monitor');
});

test('a monitor with no ready stream keeps its countdown', () => {
  const g = loadSource();
  g.currentMonitor = {monitorType: 'Ffmpeg'};
  g.monitorStream = {
    getAVStream: () => ({readyState: 0}),
    fatalError: false,
    selectedPlayer: null,
  };
  g.secondsToCycle = 5;
  let cycled = 0;
  g.cycleNext = () => {
    cycled++;
  };

  g.nextCycleView();

  assert.strictEqual(Number(g.secondsToCycle), 5,
      'the countdown should not move while the stream is not ready');
  assert.strictEqual(cycled, 0, 'an unready stream must not cycle away');
});

console.log('watch.js monitor switch without a stream');

test('streamReStart moves a volume slider with no stream', () => {
  const title = {textContent: ''};
  const imageFeed = {removeEventListener: () => {}};
  const monitorDiv = {classList: {add: () => {}}, innerHTML: ''};
  const slider = {id: 'volumeSlider1'};
  const navItem = () => ({
    querySelector: () => ({classList: {remove: () => {}, add: () => {}}}),
  });
  const doc = {
    getElementById: (id) => {
      if (id === 'monitor') return monitorDiv;
      if (id === 'volumeSlider1') return slider;
      if (id === 'nav-item-cycle1' || id === 'nav-item-cycle2') return navItem();
      return null;
    },
    querySelector: (sel) => (sel === 'title' ? title : imageFeed),
    querySelectorAll: () => [],
    addEventListener: () => {},
    createElement: () => ({}),
    location: {href: 'http://localhost/zm/index.php?view=watch&mid=1'},
  };
  const g = loadSource({
    document: doc,
    history: {pushState: () => {}},
    zmPanZoom: {action: () => {}, init: () => {}},
    eventListTable: {bootstrapTable: () => {}},
  });
  g.monitorStream = false;
  g.monitorData = [
    {id: 1, name: 'One', streamHTML: ''},
    {id: 2, name: 'Two', streamHTML: ''},
  ];
  g.controlWhatDisplay = () => {};
  g.applyMonitorControllable = () => {};
  g.streamPrepareStart = () => {};
  g.observeStreamElements = () => {};

  g.streamReStart(1, 2);

  assert.strictEqual(slider.id, 'volumeSlider2',
      'the volume slider should follow the new monitor');
});

done();
