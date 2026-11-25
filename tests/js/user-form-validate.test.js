'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/user.js'), 'utf8');

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

function load() {
  const alerts = [];
  const sandbox = {
    alert: (m) => alerts.push(m),
    window: {addEventListener: () => {}},
    console: console,
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {validateForm: sandbox.validateForm, alerts: alerts};
}

function form(fields, confirm) {
  const elements = {};
  Object.keys(fields).forEach((k) => elements[k] = {value: fields[k]});
  return {elements: elements, conf_password: {value: confirm || ''}};
}

test('self edit form without a Username field validates', () => {
  const t = load();
  assert.strictEqual(t.validateForm(form({'user[Password]': ''}), false), true);
});

test('self edit form still rejects a mismatched confirmation', () => {
  const t = load();
  assert.strictEqual(t.validateForm(form({'user[Password]': 'abc'}, 'abd'), false), false);
  assert.strictEqual(t.alerts.length, 1);
});

test('admin form without a username is rejected', () => {
  const t = load();
  assert.strictEqual(t.validateForm(form({'user[Username]': '', 'user[Password]': ''}), false), false);
});

test('admin form with a username validates', () => {
  const t = load();
  assert.strictEqual(t.validateForm(form({'user[Username]': 'bob', 'user[Password]': ''}), false), true);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
