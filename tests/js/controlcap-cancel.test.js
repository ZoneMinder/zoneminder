'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/controlcap.js'), 'utf8');

const listeners = {};
const assigned = [];
const sandbox = {
  document: {
    getElementById: (id) => ({
      addEventListener: (type, fn) => {
        listeners[id] = fn;
      },
    }),
    referrer: '',
  },
  window: {
    location: {assign: (url) => assigned.push(url)},
    history: {back: () => {}},
  },
  $j: () => ({prop: () => {}, ready: (fn) => fn()}),
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

listeners.cancelBtn({preventDefault: () => {}});

const views = fs.readdirSync(path.join(__dirname, '../../web/skins/classic/views'));
const target = new URLSearchParams(assigned[0].replace(/^\?/, '')).get('view');

assert.strictEqual(assigned.length, 1);
assert.ok(
    target === 'options' || views.includes(target + '.php'),
    'Cancel goes to view "' + target + '", which does not exist');
console.log('ok - Cancel goes to a view that exists (' + assigned[0] + ')');
