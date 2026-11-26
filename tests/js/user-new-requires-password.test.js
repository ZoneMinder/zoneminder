'use strict';

// validateForm(form, newUser) refuses an empty password for a new user, but the
// page's submit handler called validateForm(this) without newUser, so Add New
// User saved an account with no password (it can't log in). The handler now
// passes newUser when the form has no user Id yet.
//
// Run: node tests/js/user-new-requires-password.test.js [path/to/user.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.argv[2] ||
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

// Run initPage() and return the form's submit handler.
function submitHandler(alerts) {
  let handler = null;
  const chain = {
    submit: (fn) => {
      handler = fn;
      return chain;
    },
    hide: () => chain,
    prop: () => chain,
  };
  const sandbox = {
    $j: () => chain,
    alert: (m) => alerts.push(m),
    document: {getElementById: () => ({addEventListener() {}}), referrer: ''},
    window: {addEventListener() {}, history: {back() {}}, location: {reload() {}}},
    console: console,
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  sandbox.initPage();
  return handler;
}

function form(uid, password) {
  return {
    elements: {
      'uid': {value: uid},
      'user[Username]': {value: 'alice'},
      'user[Password]': {value: password},
    },
    conf_password: {value: password},
  };
}

test('a new user with no password is refused', () => {
  const alerts = [];
  const handler = submitHandler(alerts);
  assert.strictEqual(handler.call(form('', '')), false);
  assert.ok(alerts.join(' ').includes('password'), 'says a password is needed: ' + alerts);
});

test('a new user with a password is accepted', () => {
  const handler = submitHandler([]);
  assert.strictEqual(handler.call(form('', 'secret12')), true);
});

test('an existing user may leave the password empty to keep it', () => {
  const handler = submitHandler([]);
  assert.strictEqual(handler.call(form('7', '')), true);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
