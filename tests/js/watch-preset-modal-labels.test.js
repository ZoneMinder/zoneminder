'use strict';

// After switching monitors on the watch page, the global labels[] still held
// the first monitor's preset labels, so the Set Preset dialog (which is
// fetched for the current monitor) pre-filled the New Label box with the
// other monitor's label and saving wrote it to this monitor's preset. The
// dialog now supplies its own monitor's labels.
//
// Run: node tests/js/watch-preset-modal-labels.test.js [path/to/watch.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js');
const src = fs.readFileSync(file, 'utf8');

console.log('watch.js set preset dialog labels');

function open(pageLabels, dataLabels) {
  const field = {value: 'stale'};
  const $j = (sel) => {
    if (sel === '#ctrlPresetForm option:selected') return {val: () => '1'};
    if (sel === '#ctrlPresetForm') return {attr: () => dataLabels, submit() {}};
    if (sel === '#newLabel') {
      return {
        val: (v) => {
          if (v !== undefined) field.value = v;
          return field.value;
        },
      };
    }
    return makeChainable();
  };
  $j.getJSON = () => {
    const p = {done: (cb) => {
      cb({html: ''}); return p;
    }, fail: () => p};
    return p;
  };
  const globals = {
    $j, console, Object, Array, JSON, Set, parseInt, parseFloat, String, Number,
    document: {getElementById: () => null, querySelector: () => null,
      querySelectorAll: () => [], addEventListener: () => {}, createElement: () => ({})},
    setInterval: () => 1, clearInterval() {}, setTimeout: () => 0, clearTimeout() {},
    monitorData: [{id: 1}, {id: 2}], ZM_WEB_VIEWING_TIMEOUT: 0,
    addEventListener() {},
    monitorUrl: 'm', monitorId: 2,
    zmAuth: {appendTo: (u) => u}, insertModalHtml() {}, logAjaxFail() {},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : t[p]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  globals.labels = pageLabels;
  globals.controlSetClicked();
  return field;
}

test('another monitor\'s page label is not shown for a monitor without one', () => {
  assert.strictEqual(open([undefined, 'Door'], '{}').value, '');
});

test('the dialog monitor\'s own label is shown', () => {
  assert.strictEqual(open([undefined, 'Door'], '{"1":"Gate"}').value, 'Gate');
});

done();
