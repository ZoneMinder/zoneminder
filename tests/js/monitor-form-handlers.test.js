'use strict';

// Handlers on the monitor edit form that read or write fields:
//
//  - loadLocations(): choosing a Control Type rebuilds the Return Location
//    list from controlOptions, which is keyed by control Id. It looked the
//    list up with the select's selectedIndex, so any control whose Id is not
//    its position in the dropdown showed another control's presets, and a
//    control with no Home preset got an option labelled "null".
//  - ManufacturerId_onchange(): choosing Unknown for the manufacturer hands
//    the Model field back to the user. The Model select was refilled with an
//    option that has no value attribute, so it posted "Unknown" as the
//    ModelId, and the new Model text box was shown but left disabled, so
//    nothing typed into it was submitted.
//  - updateRestreamVisibility() and Restream_onclick() show the "User for RTSP
//    Server Auth" row. The view hides it when authentication is off, as the
//    list of users it offers means nothing then, but both handlers showed it
//    again as soon as Restream was ticked or the page loaded with it ticked.
//
// Run: node tests/js/monitor-form-handlers.test.js

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

// $j() hands back one recorder per selector so a test can see what was done
// to the elements that selector names.
function makeJ(state) {
  return (selector) => {
    if (!state[selector]) state[selector] = {html: [], disabled: null, shown: null};
    const rec = state[selector];
    const api = {
      empty: () => {
        rec.html = [];
        return api;
      },
      append: (h) => {
        rec.html.push(h);
        return api;
      },
      prop: (name, value) => {
        if (name === 'disabled') rec.disabled = value;
        return api;
      },
      show: () => {
        rec.shown = true;
        return api;
      },
      hide: () => {
        rec.shown = false;
        return api;
      },
      chosen: () => api,
    };
    return api;
  };
}

function load(extra) {
  const state = {};
  const globals = Object.assign({
    console: {log: () => {}, error: () => {}, warn: () => {}, trace: () => {}},
    URLSearchParams: URLSearchParams,
    Object: Object,
    Array: Array,
    JSON: JSON,
    Math: Math,
    String: String,
    Number: Number,
    setTimeout: () => 0,
    clearTimeout: () => {},
    setInterval: () => 0,
    clearInterval: () => {},
    addEventListener: () => {},
    location: {search: ''},
    history: {replaceState: () => {}},
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
    },
    Option: function(text, value) {
      this.text = String(text);
      this.value = String(value);
    },
    thisUrl: 'index.php',
  }, extra || {});
  globals.$j = makeJ(state);
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'monitor.js'});
  return {globals, state};
}

function loadLocationsFor(controlId) {
  const {globals} = load({
    controlOptions: {
      3: ['Home', 'Preset 1'],
      7: [null, 'Preset 1', 'Preset 2', 'Preset 3'],
    },
  });
  // The dropdown as the view draws it: None, then the controls by name.
  const ids = ['', '3', '7'];
  const returnLocation = {
    options: [new globals.Option('None', -1), new globals.Option('Home', 0),
      new globals.Option('Preset 1', 1)],
  };
  const controlSelect = {value: String(controlId), selectedIndex: ids.indexOf(String(controlId))};
  const form = {elements: {
    'newMonitor[ControlId]': controlSelect,
    'newMonitor[ReturnLocation]': returnLocation,
  }};
  controlSelect.form = form;
  globals.loadLocations(controlSelect);
  return returnLocation.options.map((o) => o.text + '=' + o.value);
}

test('return location lists the presets of the chosen control id', () => {
  assert.deepStrictEqual(loadLocationsFor(7),
      ['None=-1', 'Preset 1=1', 'Preset 2=2', 'Preset 3=3']);
});

test('return location offers Home only for a control that has it', () => {
  assert.deepStrictEqual(loadLocationsFor(3), ['None=-1', 'Home=0', 'Preset 1=1']);
});

test('choosing no control leaves only None', () => {
  assert.deepStrictEqual(loadLocationsFor(''), ['None=-1']);
});

function clearManufacturer() {
  const {globals, state} = load();
  const manufacturerInput = {style: {display: 'none'}, disabled: true};
  const select = {value: ''};
  select.form = {elements: {'newMonitor[Manufacturer]': manufacturerInput}};
  globals['ManufacturerId_onchange'](select); // eslint-disable-line new-cap
  return {manufacturerInput, state};
}

test('clearing the manufacturer shows and enables its name box', () => {
  const {manufacturerInput} = clearManufacturer();
  assert.strictEqual(manufacturerInput.style.display, '');
  assert.strictEqual(manufacturerInput.disabled, false);
});

test('clearing the manufacturer leaves a Model option with an empty value', () => {
  const {state} = clearManufacturer();
  const options = state['[name="newMonitor[ModelId]"]'].html.join('');
  assert(/value=""/.test(options), 'Unknown option must carry value="": ' + options);
});

test('clearing the manufacturer enables the new Model name box', () => {
  const {state} = clearManufacturer();
  const model = state['[name="newMonitor[Model]"]'];
  assert.strictEqual(model.shown, true);
  assert.strictEqual(model.disabled, false, 'a disabled input is not submitted');
});

function restreamRow(useAuth, restreamChecked, withClick) {
  const row = {style: {display: 'none'}};
  const checkbox = (checked) => ({checked: checked});
  const elements = {
    'newMonitor[RTSPServer]': checkbox(true),
    'newMonitor[JanusEnabled]': checkbox(true),
    'newMonitor[Go2RTCEnabled]': checkbox(false),
    'newMonitor[RTSP2WebEnabled]': checkbox(false),
    'newMonitor[Restream]': checkbox(restreamChecked),
  };
  const {globals} = load({
    useAuth: useAuth,
    document: {
      getElementById: (id) => {
        if (id === 'contentForm') return {elements: elements};
        if (id === 'RTSP_User') return row;
        if (id === 'FunctionRestream') return {hidden: true};
        return null;
      },
      querySelectorAll: () => [],
      addEventListener: () => {},
    },
  });
  if (withClick) {
    globals.$j = (sel) => {
      if (sel === '#RTSP_User') {
        return {show: () => (row.style.display = ''), hide: () => (row.style.display = 'none')};
      }
      return {length: 1, 0: elements['newMonitor[Restream]']};
    };
    globals['Restream_onclick'](); // eslint-disable-line new-cap
  } else {
    globals.updateRestreamVisibility();
  }
  return row.style.display;
}

test('RTSP user row stays hidden when authentication is off (page load)', () => {
  assert.strictEqual(restreamRow(false, true, false), 'none');
});

test('RTSP user row stays hidden when authentication is off (Restream ticked)', () => {
  assert.strictEqual(restreamRow(false, true, true), 'none');
});

test('RTSP user row shows with authentication on and Restream ticked', () => {
  assert.strictEqual(restreamRow(true, true, false), '');
  assert.strictEqual(restreamRow(true, true, true), '');
});

test('RTSP user row is hidden with Restream unticked', () => {
  assert.strictEqual(restreamRow(true, false, false), 'none');
  assert.strictEqual(restreamRow(true, false, true), 'none');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
