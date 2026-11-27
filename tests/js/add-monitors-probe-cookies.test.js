'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/add_monitors.js'), 'utf8');

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

const elements = [
  {name: 'probe_username', val: 'bob'},
  {name: 'probe_password', val: 's3cret'},
  {name: 'ip', val: '10.0.0.1'},
];

const cookieCalls = [];

const jq = function(arg) {
  if (typeof arg === 'string') {
    return {
      each(fn) {
        elements.forEach((el, i) => fn.call(el, i));
      },
    };
  }
  return {
    attr(name) {
      if (name === 'name') return arg.name;
      return undefined;
    },
    val() {
      return arg.val;
    },
    ready() {},
  };
};
jq.ajax = function() {};

const ctx = {
  $j: jq,
  console: console,
  document: {},
  thisUrl: 'index.php',
  zmAuth: {},
  setCookie(name, value) {
    cookieCalls.push([name, value]);
  },
};

vm.createContext(ctx);
vm.runInContext(src + ';this.api={probe};', ctx);

const params = {data: {}};
ctx.api.probe(params);

test('setCookie is called with the username cookie name', () => {
  assert.ok(
      cookieCalls.some((call) => call[0] === 'addMonitorsprobe_username'),
      'expected a cookie named addMonitorsprobe_username, got ' +
      JSON.stringify(cookieCalls.map((call) => call[0])));
});

test('setCookie is not called with the password cookie name', () => {
  assert.ok(
      !cookieCalls.some((call) => call[0] && call[0].includes('probe_password')),
      'unexpected password cookie: ' + JSON.stringify(cookieCalls));
});

test('no recorded cookie value is the password', () => {
  assert.ok(
      !cookieCalls.some((call) => call[1] === 's3cret'),
      'password was stored in a cookie: ' + JSON.stringify(cookieCalls));
});

test('the password is still sent with the request', () => {
  assert.strictEqual(params.data.probe_password, 's3cret');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
