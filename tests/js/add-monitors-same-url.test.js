'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.argv[2] ||
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
    escapeHTML: (text) => (text ? text.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : text),
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

function csvRow(name, url) {
  return {url: url, Monitor: null, camera: {Name: name,
    monitor: {Type: 'Ffmpeg', Path: url, Name: name}}};
}

function click(page, row) {
  const url = /data-url="([^"]*)"/.exec(row.buttons)[1]
      .replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
  page.api.addMonitor({getAttribute: () => url});
}

test('CSV rows with the same url but different names each add their own name', () => {
  const page = load();
  const u = 'rtsp://10.0.0.5/stream';
  const rows = page.api.decorateStreams([csvRow('Front', u), csvRow('Back', u)]);
  click(page, rows[0]);
  click(page, rows[1]);
  const first = new URL(page.navigated[0], 'http://zm.test/zm/').searchParams;
  const second = new URL(page.navigated[1], 'http://zm.test/zm/').searchParams;
  assert.strictEqual(first.get('newMonitor[Name]'), 'Front');
  assert.strictEqual(second.get('newMonitor[Name]'), 'Back');
  assert.strictEqual(first.get('newMonitor[Path]'), u);
});

test('a single row still uses the plain url as its key', () => {
  const page = load();
  const rows = page.api.decorateStreams([csvRow('Front', 'rtsp://h/a')]);
  assert.ok(rows[0].buttons.includes('data-url="rtsp://h/a"'));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
