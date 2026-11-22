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
    escapeHTML: (text) => (text ? text.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : text),
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

function remote(host, name) {
  const path = '/nphMotionJpeg?Resolution=320x240&Quality=Standard';
  return {url: path, camera: {Name: name, ip: host,
    monitor: {Type: 'Remote', Host: host, Path: path, Name: name}}};
}

function click(page, row) {
  const url = /data-url="([^"]*)"/.exec(row.buttons)[1]
      .replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
  page.api.addMonitor({getAttribute: () => url});
}

test('two remote cameras with the same relative path open their own host', () => {
  const page = load();
  const rows = page.api.decorateStreams([remote('10.0.0.5', 'A'), remote('10.0.0.6', 'B')]);
  // The second row's entry used to replace the first one's, so the first
  // camera opened with the second camera's host and name.
  click(page, rows[0]);
  let params = new URL(page.navigated[0], 'http://zm.test/zm/').searchParams;
  assert.strictEqual(params.get('newMonitor[Host]'), '10.0.0.5');
  assert.strictEqual(params.get('newMonitor[Name]'), 'A');
  click(page, rows[1]);
  params = new URL(page.navigated[1], 'http://zm.test/zm/').searchParams;
  assert.strictEqual(params.get('newMonitor[Host]'), '10.0.0.6');
  assert.strictEqual(params.get('newMonitor[Name]'), 'B');
});

test('a remote camera still gets its relative path', () => {
  const page = load();
  const rows = page.api.decorateStreams([remote('10.0.0.5', 'A')]);
  click(page, rows[0]);
  const params = new URL(page.navigated[0], 'http://zm.test/zm/').searchParams;
  assert.strictEqual(params.get('newMonitor[Path]'),
      '/nphMotionJpeg?Resolution=320x240&Quality=Standard');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
