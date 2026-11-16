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

function loadLogger() {
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

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
