'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js'), 'utf8');
const view = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/monitor.php'), 'utf8');

const editButton = {disabled: true};
const globals = {
  console: {log: () => {}, error: () => {}, trace: () => {}},
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
    getElementById: (id) => (id === 'ControlEdit' ? editButton : null),
    querySelectorAll: () => [],
    addEventListener: () => {},
  },
  $j: () => ({on: () => {}}),
};
const sandbox = new Proxy(globals, {
  has: () => true,
  get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
});
globals.window = sandbox;
vm.createContext(sandbox);
vm.runInContext(src, sandbox, {filename: 'monitor.js'});

const onChange = globals['ControlId_onChange'];
onChange({value: '3'});
assert.strictEqual(editButton.disabled, false, 'picking a control enables Edit');
onChange({value: ''});
assert.strictEqual(editButton.disabled, true, 'picking None disables Edit');

assert.ok(/'data-on-change-this'=>'ControlId_onChange'/.test(view),
    'the Control Type select must call ControlId_onChange on change, the click handler is not bound to selects');
assert.ok(/id="ControlEdit"/.test(view), 'the Edit button must be rendered whether or not a control is set');
console.log('ok - the Edit button follows the Control Type select');
