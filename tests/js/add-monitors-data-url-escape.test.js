'use strict';

// A stream url with a double quote broke out of the data-url attribute of the Add/Edit button.
// Run as: node tests/js/add-monitors-data-url-escape.test.js [path to add_monitors.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/add_monitors.js');
const src = fs.readFileSync(file, 'utf8');

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
    // as in skin.js
    escapeHTML: function(text) {
      if (!text) return text;
      return text.toString()
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    },
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

function stream(url) {
  return {url: url, camera: {Name: 'A', monitor: {Type: 'Ffmpeg', Path: url, Name: 'A'}}};
}

const evil = 'rtsp://h/x"onmouseover="alert(1)';

test('a double quote in the url cannot close the data-url attribute', () => {
  const rows = load().api.decorateStreams([stream(evil)]);
  assert.ok(!rows[0].buttons.includes('x"onmouseover='), rows[0].buttons);
});

test('the data-url attribute value reads back as the url', () => {
  const rows = load().api.decorateStreams([stream(evil)]);
  const m = rows[0].buttons.match(/data-url="([^"]*)"/);
  assert.ok(m, rows[0].buttons);
  assert.strictEqual(m[1].replace(/&quot;/g, '"').replace(/&amp;/g, '&'), evil);
});

test('a plain url is unchanged', () => {
  const rows = load().api.decorateStreams([stream('rtsp://10.0.0.1/a')]);
  assert.ok(rows[0].buttons.includes('data-url="rtsp://10.0.0.1/a"'), rows[0].buttons);
});

test('the button still opens the new monitor form', () => {
  const page = load();
  page.api.decorateStreams([stream(evil)]);
  page.api.addMonitor({getAttribute: () => evil});
  assert.ok(page.navigated[0].startsWith('?view=monitor'), page.navigated[0]);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
