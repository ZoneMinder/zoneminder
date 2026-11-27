'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.FRAME_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js');
const src = fs.readFileSync(srcPath, 'utf8');

let windowWidth = 800;
const tableState = {visible: true};
const table = {
  is: () => tableState.visible,
  toggle: (show) => {
    tableState.visible = show;
    return table;
  },
  empty: () => table,
  append: () => table,
};
function makeEl() {
  const el = {
    val: () => '640px',
    css: () => el,
    prop: (name, value) => (value === undefined ? '' : el),
    off: () => el,
    on: () => el,
    toggle: () => el,
    empty: () => el,
    append: () => el,
    ready: () => el,
    is: () => true,
  };
  return el;
}

const win = {location: {}};
const sandbox = {
  $j: Object.assign(function(selector) {
    if (selector === win) return {width: () => windowWidth, off: () => win, on: () => win};
    if (selector === '#frameStatsTable') return table;
    return makeEl();
  }, {
    each: (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k])),
    getJSON: () => {
      const p = {done: () => p, fail: () => p};
      return p;
    },
  }),
  window: win,
  document: {
    referrer: '',
    getElementById: () => ({addEventListener() {}}),
  },
  console: {log() {}},
  setCookie: () => {},
  getCookie: () => 'off',
  bindButton: () => {},
  scaleToFit: () => ({width: 1000, height: 600, autoScale: 100}),
  logAjaxFail: () => {},
  thisUrl: '',
  statHeaderStrings: {},
  scale: '640px',
  eid: 1,
  fid: 1,
  SCALE_BASE: 100,
};

vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'frame.js'});

// The user turned the stats off and opened the page in a narrow window.
sandbox.initPage();
assert.strictEqual(tableState.visible, false, 'stats table should be hidden at load');

// Widening the window runs changeScale -> onStatsResize.
windowWidth = 2000;
sandbox.onStatsResize(640);
assert.strictEqual(tableState.visible, false,
    'widening the window showed the stats table although the user turned stats off');
console.log('  ok stats table stays off after widening the window');
