'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
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

function load() {
  const navigated = [];
  const jq = function() {
    return {on() {}, each() {}, ready() {}, show() {}, bootstrapTable() {}, modal() {}};
  };
  jq.ajax = function() {};
  const ctx = {
    $j: jq,
    console: console,
    thisUrl: 'index.php',
    zmAuth: {},
    user: {Monitors: 'Create'},
    ZM_WEB_LIST_THUMBS: 0,
    ZM_WEB_LIST_THUMB_WIDTH: 96,
    ZM_ENABLE_CSRF_MAGIC: '0',
    icons: {},
    setCookie() {},
    dataOnClickThis() {},
    insertModalHtml() {},
    logAjaxFail() {},
    alert: function(msg) {
      navigated.push('ALERT ' + msg);
    },
    document: {getElementById: () => ({addEventListener() {}})},
    window: {location: {assign: (u) => navigated.push(u)}},
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.api = {decorateStreams, addMonitor};', ctx);
  return {api: ctx.api, navigated: navigated};
}

function stream(url, name) {
  return {url: url, camera: {Name: name, monitor: {Type: 'Ffmpeg', Path: url, Name: name}}};
}

function addUrlFor(url, name) {
  const page = load();
  page.api.decorateStreams([stream(url, name)]);
  page.api.addMonitor({getAttribute: () => url});
  return new URL(page.navigated[0], 'http://zm.test/zm/');
}

test('a path with a query string keeps every parameter', () => {
  const url = 'rtsp://10.0.0.1/cam/realmonitor?channel=1&subtype=0&unicast=true';
  const params = addUrlFor(url, 'Front').searchParams;
  assert.strictEqual(params.get('newMonitor[Path]'), url);
});

test('a name with an ampersand, a hash and a plus survives', () => {
  const params = addUrlFor('rtsp://10.0.0.2/a', 'Front & Back #1 +2').searchParams;
  assert.strictEqual(params.get('newMonitor[Name]'), 'Front & Back #1 +2');
});

test('a percent-encoded password in the path is not decoded', () => {
  const url = 'rtsp://admin:p%40ss%26x@10.0.0.3/a';
  const params = addUrlFor(url, 'Door').searchParams;
  assert.strictEqual(params.get('newMonitor[Path]'), url);
});

test('no stray top level parameters are created', () => {
  const keys = Array.from(addUrlFor('rtsp://10.0.0.4/a?x=1&y=2', 'A & B').searchParams.keys());
  keys.forEach((k) => assert.ok(k === 'view' || k.startsWith('newMonitor['), k));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
