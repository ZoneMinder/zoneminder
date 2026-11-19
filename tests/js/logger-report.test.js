'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(path.join(__dirname, '../../web/js/logger.js'), 'utf8');

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

function loadLogger(config = {}) {
  const posts = [];
  const $j = {
    ajaxSetup: () => {},
    post: (url, data) => posts.push(data),
  };
  const sandbox = {
    $j: $j,
    ZM_LOG_INJECT: '1',
    canEdit: {System: false},
    AJAX_TIMEOUT: 1000,
    navigator: {userAgent: 'Chrome/120.0', appName: 'Netscape', appVersion: '5.0'},
    thisUrl: 'index.php',
    location: {search: ''},
    addEventListener: () => {},
    console: {log() {}, warn() {}, error() {}, debug() {}},
    posts: posts,
  };
  Object.assign(sandbox, config);
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'logger.js'});
  return sandbox;
}

console.log('client log reports');
test('the message is posted as typed, jQuery does the URL encoding', () => {
  const sandbox = loadLogger();
  sandbox.logReport('ERR', 'TypeError: x is undefined 100% done', 'a.js', '3:4');
  assert.strictEqual(sandbox.posts.length, 1);
  assert.strictEqual(sandbox.posts[0].message, 'TypeError: x is undefined 100% done');
  assert.strictEqual(sandbox.posts[0].level, 'ERR');
});

// skin.js.php writes a boolean option that is off as '' (PHP empty('0')), not '0'.
console.log('boolean options switched off');
test('ZM_LOG_INJECT off does not post for a user without System edit', () => {
  const sandbox = loadLogger({ZM_LOG_INJECT: ''});
  sandbox.logReport('ERR', 'boom', 'a.js', 1);
  assert.strictEqual(sandbox.posts.length, 0);
});

test('ZM_LOG_INJECT off still posts for a user with System edit', () => {
  const sandbox = loadLogger({ZM_LOG_INJECT: '', canEdit: {System: true}});
  sandbox.logReport('ERR', 'boom', 'a.js', 1);
  assert.strictEqual(sandbox.posts.length, 1);
});

test('ZM_LOG_BROWSER_EXTENSIONS off drops errors from an extension', () => {
  const sandbox = loadLogger({ZM_LOG_BROWSER_EXTENSIONS: ''});
  sandbox.onerror('boom', 'chrome-extension://abc/content.js', 1, 1, null);
  assert.strictEqual(sandbox.posts.length, 0);
});

test('ZM_LOG_BROWSER_EXTENSIONS on logs errors from an extension', () => {
  const sandbox = loadLogger({ZM_LOG_BROWSER_EXTENSIONS: '1'});
  sandbox.onerror('boom', 'chrome-extension://abc/content.js', 1, 1, null);
  assert.strictEqual(sandbox.posts.length, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
