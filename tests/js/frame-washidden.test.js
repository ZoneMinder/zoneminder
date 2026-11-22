'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.FRAME_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js');
const src = fs.readFileSync(srcPath, 'utf8');

function makeEl(visible) {
  const el = {
    val: () => '100',
    css: () => el,
    prop: () => el,
    off: () => el,
    on: () => el,
    toggle: () => el,
    empty: () => el,
    append: () => el,
    width: () => 2000,
    is: (what) => (what === ':visible' ? visible : false),
    ready: () => el,
  };
  return el;
}

// The stats table is hidden (Stats button or zmFrameStats=off cookie) and the
// window is wide, so onStatsResize takes the "show it again" branch.
const table = makeEl(false);
const sandbox = {
  $j: Object.assign(function(selector) {
    if (selector === '#frameStatsTable') return table;
    return makeEl(true);
  }, {each: (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k]))}),
  window: {width: () => 2000, location: {}},
  document: {referrer: ''},
  console: {log() {}},
  setCookie: () => {},
  getCookie: () => 'off',
  SCALE_BASE: 100,
};

vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'frame.js'});

assert.doesNotThrow(
    () => sandbox.onStatsResize(100),
    'onStatsResize threw when the stats table was hidden');
console.log('  ok onStatsResize handles a hidden stats table without throwing');
