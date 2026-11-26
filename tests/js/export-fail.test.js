'use strict';

// exportFail is the AJAX error callback of the export request. It used to take
// no parameters and call logAjaxFail() without arguments, so logAjaxFail read
// jqxhr.statusText of undefined and threw a TypeError inside the error
// callback, and the failure was never logged.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.env.EXPORT_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/export.js'), 'utf8');

const logCalls = [];

function chainable() {
  const obj = {
    addClass: () => obj,
    removeClass: () => obj,
    text: () => obj,
    html: () => obj,
    append: () => obj,
    serialize: () => '',
    prop: () => obj,
  };
  return obj;
}

const $j = function() {
  return chainable();
};
$j.ajax = () => chainable();
$j.getJSON = () => chainable();

const sandbox = {
  console: {log() {}, warn() {}, error() {}},
  $j: $j,
  clearInterval: () => {},
  setInterval: () => 1,
  setTimeout: () => 1,
  document: {
    getElementById: () => ({addEventListener: () => {}, elements: {}}),
    referrer: '',
  },
  window: {addEventListener: () => {}, location: {replace() {}}, history: {back() {}}},
  // The real logAjaxFail(jqxhr, textStatus, error) dereferences jqxhr at once.
  logAjaxFail: (jqxhr, ...rest) => {
    if (jqxhr === undefined) {
      throw new TypeError("Cannot read properties of undefined (reading 'statusText')");
    }
    logCalls.push([jqxhr, ...rest]);
  },
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'export.js'});

const jqxhr = {statusText: 'Internal Server Error', status: 500};
assert.doesNotThrow(
    () => sandbox.exportFail(jqxhr, 'error', 'x'),
    'exportFail must not throw when logAjaxFail needs the jqxhr');
assert.strictEqual(logCalls.length, 1, 'logAjaxFail must be called once');
assert.strictEqual(logCalls[0][0], jqxhr, 'the jqxhr must be forwarded to logAjaxFail');
assert.strictEqual(logCalls[0][1], 'error');
assert.strictEqual(logCalls[0][2], 'x');
console.log('ok - exportFail forwards the jqxhr to logAjaxFail');
