'use strict';

// A navbar refresh time of 0 made setInterval(getNavBar, 0) poll the server
// continuously. 0 must leave the periodic refresh off; a positive time must
// still start it.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skinPath = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/js/skin.js');
const skin = fs.readFileSync(skinPath, 'utf8');

const start = skin.indexOf('// Trigger autorefresh of the widget bar stats');
const end = skin.indexOf('// Update zmBandwidth cookie');
assert.ok(start >= 0 && end > start, 'navbar refresh block not found');
const block = skin.slice(start, end);

function run(navBarRefresh) {
  const calls = [];
  const ctx = {
    $j: () => ({length: 1}),
    getNavBar: function getNavBar() {},
    setInterval: (fn, ms) => calls.push(ms),
    navBarRefresh,
  };
  vm.createContext(ctx);
  vm.runInContext(block, ctx);
  return calls;
}

assert.deepStrictEqual(run(0), [], 'refresh time 0 must not start a poll');
assert.deepStrictEqual(run(15000), [15000], 'positive refresh time starts the poll');
console.log('ok');
