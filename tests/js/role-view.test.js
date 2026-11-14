'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = path.join(__dirname, '../../web/skins/classic/views/js/role.js');

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

function load(referrer) {
  const handlers = {};
  const disabled = {};
  const history = {backs: 0};
  const location = {reloads: 0, reload: () => location.reloads++};
  const listeners = {};
  const sandbox = {
    window: {
      addEventListener: (n, fn) => listeners[n] = fn,
      history: {back: () => history.backs++},
      location: location,
    },
    document: {
      referrer: referrer,
      getElementById: (id) => ({addEventListener: (ev, fn) => handlers[id] = fn}),
    },
    $j: (sel) => ({prop: (name, v) => disabled[sel] = v}),
  };
  vm.createContext(sandbox);
  vm.runInContext(fs.readFileSync(jsPath, 'utf8'), sandbox);
  const onLoad = listeners.DOMContentLoaded;
  onLoad();
  return {handlers, disabled, history, location};
}

test('back button is enabled when there is a referrer', () => {
  const t = load('http://zm/?view=options');
  assert.strictEqual(t.disabled['#backBtn'], false);
  t.handlers.backBtn({preventDefault: () => {}});
  assert.strictEqual(t.history.backs, 1);
});

test('back button stays disabled without a referrer', () => {
  assert.strictEqual(load('').disabled['#backBtn'], true);
});

test('refresh button reloads the page', () => {
  const t = load('');
  t.handlers.refreshBtn({preventDefault: () => {}});
  assert.strictEqual(t.location.reloads, 1);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
