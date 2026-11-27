'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/export.js'), 'utf8');

function run(opts) {
  const chk = (c) => ({checked: c});
  const button = {disabled: null};
  const form = {
    elements: {
      'exportButton': button,
      'eids[]': new Array(opts.events).fill({checked: false}),
      'exportDetail': chk(opts.detail), 'exportFrames': chk(false), 'exportImages': chk(false),
      'exportVideo': chk(false), 'exportMisc': chk(false),
      'exportFormat': [chk(false), chk(true)],
      'exportCompress': [chk(false), chk(true)],
    },
    querySelectorAll: () => new Array(opts.events).fill({checked: false}),
  };
  const sandbox = {
    document: {getElementById: () => form}, window: {addEventListener() {}},
    console, $j: () => ({}),
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.configureExportButton();
  return button.disabled;
}

assert.strictEqual(run({detail: true, events: 1}), false, 'options and event: enabled');
assert.strictEqual(run({detail: false, events: 3}), true, 'no option ticked: disabled');
assert.strictEqual(run({detail: true, events: 0}), true, 'no events: disabled');
console.log('ok export button state');
