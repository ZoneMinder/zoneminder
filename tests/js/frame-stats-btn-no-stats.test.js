'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.FRAME_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js');
const src = fs.readFileSync(srcPath, 'utf8');

function makeEl(state) {
  const el = {
    val: () => '100',
    css: () => el,
    off: () => el,
    on: () => el,
    toggle: () => el,
    empty: () => el,
    append: () => el,
    width: () => 2000,
    is: () => true,
    ready: () => el,
    prop: (name, value) => {
      if (value === undefined) return state[name];
      state[name] = value;
      return el;
    },
  };
  return el;
}

const btnState = {disabled: false};
const statsBtn = makeEl(btnState);
const other = makeEl({});
const sandbox = {
  $j: Object.assign(function(selector) {
    if (selector === '#statsBtn') return statsBtn;
    return other;
  }, {
    each: (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k])),
    getJSON: () => {
      const p = {
        done: (fn) => {
          fn({result: 'Ok', raw: []});
          return p;
        },
        fail: () => p,
      };
      return p;
    },
  }),
  window: {width: () => 2000, location: {}},
  document: {referrer: ''},
  console: {log() {}},
  setCookie: () => {},
  getCookie: () => 'on',
  thisUrl: '',
  logAjaxFail: () => {},
  statHeaderStrings: {},
  SCALE_BASE: 100,
};

vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'frame.js'});

// A frame without stats: getStat disables the button.
sandbox.getStat({eid: 1, fid: 1});
assert.strictEqual(btnState.disabled, true, 'getStat should disable the button');

// A window resize runs changeScale -> onStatsResize with plenty of room.
sandbox.onStatsResize(100);
assert.strictEqual(btnState.disabled, true,
    'resizing the window re-enabled the Stats button of a frame that has no stats');
console.log('  ok Stats button stays disabled for a frame without stats after a resize');
