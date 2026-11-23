'use strict';

// insertModalHtml() must ignore anything that is not a non-empty string
// instead of handing it to jQuery's replaceWith()/append(). modalReplyFailed()
// is the global ajaxError handler for modal loads: it alerts only when the
// failed request was a request=modal call that replied with result "Error".

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = path.resolve(
    process.argv[2] || path.join(__dirname, '../../web/skins/classic/js/skin.js'));
const src = fs.readFileSync(srcPath, 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

// The functions are indented inside skin.js; anchor the closing brace on the
// same indentation as the opening line.
function extractFn(name) {
  const pattern = new RegExp(
      '^([ \\t]*)function ' + name + '\\([\\s\\S]*?^\\1\\}\\s*$', 'm');
  const m = src.match(pattern);
  if (!m) throw new Error(srcPath + ' should define ' + name + '()');
  return m[0];
}

// $j() returns a stub per selector, cached so identity checks work. The stubs
// record replaceWith()/append() and report .length from the existing modals.
function makeEnv(existingNames) {
  const calls = [];
  const alerts = [];
  const existing = new Set(existingNames);
  const cache = new Map();

  function stub(sel) {
    if (cache.has(sel)) return cache.get(sel);
    const length = typeof sel === 'string' && sel[0] === '#'
      ? (existing.has(sel.slice(1)) ? 1 : 0) : 0;
    const el = {
      length: length,
      replaceWith: function() {
        calls.push({method: 'replaceWith', args: Array.prototype.slice.call(arguments)});
        return el;
      },
      append: function() {
        calls.push({method: 'append', args: Array.prototype.slice.call(arguments)});
        return el;
      },
      addClass: function() {
        return el;
      },
      removeClass: function() {
        return el;
      },
      attr: function() {
        return el;
      },
      is: function() {
        return false;
      },
      on: function() {
        return el;
      },
      modal: function() {
        return el;
      },
      find: function() {
        return el;
      },
    };
    cache.set(sel, el);
    return el;
  }

  const $j = function(sel) {
    return stub(sel);
  };
  $j.each = function(obj, fn) {
    Object.keys(obj).forEach(function(k) {
      fn(k, obj[k]);
    });
  };

  const globals = {
    $j: $j,
    alert: function(msg) {
      alerts.push(msg);
    },
    console: {log: function() {}, warn: function() {}, error: function() {}, debug: function() {}},
  };
  const sandbox = new Proxy(globals, {
    has: function() {
      return true;
    },
    get: function(target, prop) {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
    set: function(target, prop, value) {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);

  // Compile a function from skin.js on first use, so a missing definition
  // fails only the tests that need it.
  function define(name) {
    return function() {
      if (typeof globals[name] !== 'function') {
        vm.runInContext(extractFn(name), sandbox, {filename: 'skin.js'});
      }
      return globals[name].apply(null, arguments);
    };
  }

  return {
    insertModalHtml: define('insertModalHtml'),
    modalReplyFailed: define('modalReplyFailed'),
    $j: $j,
    calls: calls,
    alerts: alerts,
  };
}

function methodCalls(env, method) {
  return env.calls.filter(function(c) {
    return c.method === method;
  });
}

function errorReply(env, url, message) {
  const responseJSON = {result: 'Error'};
  if (message !== undefined) responseJSON.message = message;
  env.modalReplyFailed({}, {responseJSON: responseJSON}, {url: url});
}

console.log('modal load error replies');

test('insertModalHtml with invalid html does not replaceWith an existing modal', () => {
  const env = makeEnv(['fooModal']);
  for (const html of ['', null, undefined, 0, false]) {
    const result = env.insertModalHtml('fooModal', html);
    assert.strictEqual(methodCalls(env, 'replaceWith').length, 0,
        'replaceWith must not be called for ' + JSON.stringify(html));
    assert.strictEqual(result, env.$j('#fooModal'),
        'should return $j("#fooModal") for ' + JSON.stringify(html));
  }
});

test('insertModalHtml with invalid html does not append when the modal is absent', () => {
  const env = makeEnv([]);
  for (const html of ['', null, undefined, 0, false]) {
    const result = env.insertModalHtml('fooModal', html);
    assert.strictEqual(methodCalls(env, 'append').length, 0,
        'append must not be called for ' + JSON.stringify(html));
    assert.strictEqual(methodCalls(env, 'replaceWith').length, 0,
        'replaceWith must not be called for ' + JSON.stringify(html));
    assert.strictEqual(result, env.$j('#fooModal'),
        'should return $j("#fooModal") for ' + JSON.stringify(html));
  }
});

test('insertModalHtml with valid html replaces an existing modal', () => {
  const env = makeEnv(['fooModal']);
  const html = '<div id="fooModal">hi</div>';
  const result = env.insertModalHtml('fooModal', html);
  const replaces = methodCalls(env, 'replaceWith');
  assert.strictEqual(replaces.length, 1, 'replaceWith should be called once');
  assert.strictEqual(replaces[0].args[0], html, 'replaceWith should get the html');
  assert.strictEqual(methodCalls(env, 'append').length, 0, 'append must not be called');
  assert.strictEqual(result, env.$j('#fooModal'), 'should return the modal');
});

test('insertModalHtml with valid html appends a new modal to body', () => {
  const env = makeEnv([]);
  const html = '<div id="fooModal">hi</div>';
  const result = env.insertModalHtml('fooModal', html);
  const appends = methodCalls(env, 'append');
  assert.strictEqual(appends.length, 1, 'append should be called once');
  assert.strictEqual(appends[0].args[0], html, 'append should get the html');
  assert.strictEqual(methodCalls(env, 'replaceWith').length, 0,
      'replaceWith must not be called');
  assert.strictEqual(result, env.$j('#fooModal'), 'should return the new modal');
});

test('modalReplyFailed alerts with the server message for a modal error', () => {
  const env = makeEnv([]);
  errorReply(env, 'index.php?request=modal&modal=foo', 'Boom');
  assert.deepStrictEqual(env.alerts, ['Boom']);
});

test('modalReplyFailed falls back to a generic message when none is supplied', () => {
  const env = makeEnv([]);
  errorReply(env, 'index.php?view=request&request=modal&modal=foo');
  assert.deepStrictEqual(env.alerts, ['Unable to load the dialog.']);
});

test('modalReplyFailed ignores errors from non-modal requests', () => {
  const env = makeEnv([]);
  errorReply(env, 'index.php?view=request&request=status&entity=navBar', 'Boom');
  assert.deepStrictEqual(env.alerts, []);
});

test('modalReplyFailed ignores successful modal replies', () => {
  const env = makeEnv([]);
  env.modalReplyFailed({}, {responseJSON: {result: 'Success'}},
      {url: 'index.php?request=modal&modal=foo'});
  assert.deepStrictEqual(env.alerts, []);
});

test('modalReplyFailed ignores a reply without responseJSON', () => {
  const env = makeEnv([]);
  env.modalReplyFailed({}, {}, {url: 'index.php?request=modal&modal=foo'});
  assert.deepStrictEqual(env.alerts, []);
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
