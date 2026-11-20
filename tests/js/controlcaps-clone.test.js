'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/controlcaps.js'), 'utf8');

const assigned = [];
const sandbox = {
  document: {addEventListener: () => {}},
  window: {location: {assign: (url) => assigned.push(url)}},
  $j: {map: (arr, fn) => arr.map(fn)},
  strip_html: (s) => String(s),
  tableControlCaps: {bootstrapTable: () => [{Id: 7}]},
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

assert.strictEqual(typeof sandbox.cloneControl, 'function', 'cloneControl is not defined');
sandbox.cloneControl({getAttribute: () => '?view=controlcap&dupId='});
assert.deepStrictEqual(assigned, ['?view=controlcap&dupId=7']);
console.log('ok - Clone opens the control editor on a copy of the selected control');
