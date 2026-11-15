'use strict';

// Loads watch.js into a vm context for the tests. Unknown globals resolve to
// undefined; pass overrides for the ones a test cares about.

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js'), 'utf8');

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => {
      if (prop === 'then') return undefined;
      if (prop === 'val') return () => '10';
      if (prop === 'text') return () => '';
      return proxy;
    },
    apply: () => proxy,
  });
  return proxy;
}

// A jQuery-like wrapper that keeps real handler lists, so tests can count how
// many times a handler would fire.
function makeHandlerSet() {
  const handlers = {};
  const obj = {
    handlers,
    length: 1,
    on(evt, fn) {
      (handlers[evt] = handlers[evt] || []).push(fn);
      return obj;
    },
    off(evt, fn) {
      if (!handlers[evt]) return obj;
      handlers[evt] = fn ? handlers[evt].filter((f) => f !== fn) : [];
      return obj;
    },
    prop: () => obj, attr: () => obj, addClass: () => obj, removeClass: () => obj,
    hasClass: () => false, hide: () => obj, show: () => obj, html: () => obj,
    is: () => false,
  };
  return obj;
}

function loadWatch(overrides = {}) {
  const globals = Object.assign({
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setInterval: () => 1,
    clearInterval: () => {},
    setTimeout: () => 0,
    clearTimeout: () => 0,
    Object, Array, JSON, Set, parseInt, parseFloat, String, Number,
    monitorData: [{id: 1}, {id: 2}],
    ZM_WEB_VIEWING_TIMEOUT: 0,
    addEventListener: () => {},
  }, overrides);
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  return globals;
}

function runner() {
  const state = {passed: 0, failed: 0};
  return {
    test(name, fn) {
      try {
        fn();
        console.log('  ok ' + name);
        state.passed++;
      } catch (e) {
        console.error('  FAIL ' + name);
        console.error('    ' + (e.stack || e.message));
        state.failed++;
      }
    },
    done() {
      console.log('\n' + state.passed + ' passed, ' + state.failed + ' failed');
      process.exit(state.failed ? 1 : 0);
    },
  };
}

module.exports = {loadWatch, makeChainable, makeHandlerSet, runner};
