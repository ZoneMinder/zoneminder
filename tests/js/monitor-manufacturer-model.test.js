'use strict';

// Choosing a manufacturer on the monitor form refills the Model list and
// leaves Unknown selected. With Unknown chosen the Model name box is how a
// model is entered, so it has to be shown and enabled, as it is when the page
// loads with no model and when the manufacturer is cleared. It stayed hidden
// and disabled if a model had been chosen before, so no model could be typed
// for the new manufacturer and a save dropped the model.
//
// Run: node tests/js/monitor-manufacturer-model.test.js

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

// The Model name box starts as the view draws it when a model is chosen:
// hidden and disabled.
function load() {
  const model = {shown: false, disabled: true};
  const modelList = {options: []};
  const $j = (selector) => {
    const api = {
      length: 1,
      empty: () => {
        if (selector === '[name="newMonitor[ModelId]"]') modelList.options = [];
        return api;
      },
      append: (html) => {
        if (selector === '[name="newMonitor[ModelId]"]') modelList.options.push(html);
        return api;
      },
      prop: (name, value) => {
        if (selector === '[name="newMonitor[Model]"]' && name === 'disabled') model.disabled = value;
        return api;
      },
      show: () => {
        if (selector === '[name="newMonitor[Model]"]') model.shown = true;
        return api;
      },
      hide: () => {
        if (selector === '[name="newMonitor[Model]"]') model.shown = false;
        return api;
      },
      chosen: () => api,
    };
    return api;
  };
  $j.getJSON = () => {};
  const globals = {
    console: {log: () => {}, error: () => {}, warn: () => {}},
    addEventListener: () => {},
    document: {getElementById: () => null, querySelectorAll: () => []},
    thisUrl: 'index.php',
    $j: $j,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'monitor.js'});
  return {globals, model, modelList};
}

function manufacturerForm(selectedValue) {
  const manufacturerInput = {style: {display: ''}, disabled: false, value: ''};
  const select = {
    value: selectedValue,
    selectedIndex: 0,
    options: [{text: 'Unknown', value: ''}, {text: 'Axis', value: '7'}],
  };
  select.form = {elements: {
    'newMonitor[Manufacturer]': manufacturerInput,
    'newMonitor[ManufacturerId]': select,
  }};
  manufacturerInput.form = select.form;
  return {select, manufacturerInput};
}

test('choosing a manufacturer shows and enables the Model name box', () => {
  const {globals, model, modelList} = load();
  const {select} = manufacturerForm('7');
  globals['ManufacturerId_onchange'](select); // eslint-disable-line new-cap
  assert.ok(/value=""/.test(modelList.options.join('')), 'Model list is reset to Unknown');
  assert.strictEqual(model.shown, true, 'Model name box is shown');
  assert.strictEqual(model.disabled, false, 'a disabled input is not submitted');
});

test('typing a manufacturer name shows and enables the Model name box', () => {
  const {globals, model} = load();
  const {manufacturerInput} = manufacturerForm('');
  manufacturerInput.value = 'axis';
  globals['Manufacturer_onchange'](manufacturerInput); // eslint-disable-line new-cap
  assert.strictEqual(model.shown, true, 'Model name box is shown');
  assert.strictEqual(model.disabled, false, 'a disabled input is not submitted');
});

test('clearing the manufacturer still shows and enables the Model name box', () => {
  const {globals, model} = load();
  const {select} = manufacturerForm('');
  globals['ManufacturerId_onchange'](select); // eslint-disable-line new-cap
  assert.strictEqual(model.shown, true);
  assert.strictEqual(model.disabled, false);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
