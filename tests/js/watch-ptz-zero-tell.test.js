'use strict';

// The PTZ arrows on the watch page carry data-xtell/data-ytell. 0 means the
// click position on that axis does not matter (Up has data-xtell="0", Left has
// data-ytell="0"). getAttribute() returns the string "0", which is truthy, so
// controlCmd() sent the raw click position as xge/yge for the unused axis. The
// control request then got a --panspeed for a straight tilt (or --tiltspeed
// for a straight pan) and, with an auto stop timeout set, that stray speed can
// drop --autostop. Same fix and same cases as ptz-zero-tell.test.js, but for
// watch.js. The watch.js path can be overridden via argv[2] so the test can be
// run against an unmodified copy of the file.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {makeChainable, runner} = require('./watch-sandbox');

const {test, done} = runner();

const watchPath = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js');

// Loads watch.js like watch-sandbox.js loadWatch(), but from an arbitrary
// path (watch-sandbox.js itself must stay untouched and always reads the
// real watch.js).
function loadWatchFrom(file, overrides = {}) {
  const src = fs.readFileSync(file, 'utf8');
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
  vm.runInContext(src, sandbox, {filename: path.basename(file)});
  return globals;
}

// A 100x100 button at (0,0), clicked at (95, 80).
function makeButton(value, xtell, ytell) {
  return {
    attrs: {'value': value, 'data-xtell': xtell, 'data-ytell': ytell},
    getAttribute(k) {
      return this.attrs[k];
    },
  };
}
const click = (button) => ({type: 'mousedown', target: button, currentTarget: button, pageX: 95, pageY: 80});

function controlSend(button) {
  const sent = [];
  // A chainable jQuery stand-in: watch.js top-level code runs all sorts of
  // $j(...).on(...) chains, but controlCmd() reads offset/width/height from
  // the clicked button.
  const box = new Proxy(function() {}, {
    get: (target, prop) => {
      if (prop === 'then') return undefined;
      if (prop === 'offset') return () => ({left: 0, top: 0});
      if (prop === 'width') return () => 100;
      if (prop === 'height') return () => 100;
      if (prop === 'val') return () => '1';
      if (prop === 'text') return () => '';
      return box;
    },
    apply: () => box,
  });
  const $j = function() {
    return box;
  };
  $j.getJSON = function(url, data) {
    sent.push(data);
    const p = {done: () => p, fail: () => p};
    return p;
  };
  const g = loadWatchFrom(watchPath, {
    $j, zmAuth: {}, monitorUrl: '', monitorId: 1, streamMode: 'jpeg',
  });
  g.controlCmd(click(button));
  return sent[0];
}

console.log('watch.js: ' + watchPath);

test('watch.js: Up sends no pan factor', () => {
  const data = controlSend(makeButton('moveConUp', '0', '-1'));
  assert.strictEqual(data.control, 'moveConUp');
  assert.strictEqual(data.xge, undefined, 'xge ' + data.xge);
  assert.strictEqual(data.yge, 20);
});
test('watch.js: Left sends no tilt factor', () => {
  const data = controlSend(makeButton('moveConLeft', '-1', '0'));
  assert.strictEqual(data.xge, 5);
  assert.strictEqual(data.yge, undefined, 'yge ' + data.yge);
});
test('watch.js: a diagonal still sends both', () => {
  const data = controlSend(makeButton('moveConDownRight', '1', '1'));
  assert.strictEqual(data.xge, 95);
  assert.strictEqual(data.yge, 80);
});
test('watch.js: no data-xtell/data-ytell sends neither', () => {
  const data = controlSend(makeButton('moveConUp', undefined, undefined));
  assert.strictEqual(data.control, 'moveConUp');
  assert.strictEqual(data.xge, undefined, 'xge ' + data.xge);
  assert.strictEqual(data.yge, undefined, 'yge ' + data.yge);
});

done();
