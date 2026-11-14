'use strict';

// URL.protocol includes the trailing colon, so comparing it with 'https' was
// never true and the RTSP2Web MSE websocket was always ws://, which a browser
// blocks from an https page.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');

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

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

function mseUrlFor(path2web) {
  const el = {nodeName: 'VIDEO', setAttribute() {}, muted: false};
  const seen = [];
  const globals = {
    Object, Math, Array, JSON, URL, Set, Map, Date, parseInt, String, Number,
    $j: makeChainable(), $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
    getCookie: () => 'true',
    ZM_RTSP2WEB_PATH: path2web,
    replaceDOMElement: () => el,
    generateUUID: () => 'u',
    document: {getElementById: () => null, createElement: () => ({})},
    setInterval: () => 1, clearInterval() {}, setTimeout: () => 0,
    clearTimeout() {},
    statusRefreshTimeout: 1000,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  globals.startMsePlay = (ctx, v, url) => seen.push(url);
  const m = new globals.MonitorStream({
    id: 5, name: 't', connKey: '1', url: 'i', url_to_zms: 'z',
    width: 640, height: 480, RTSP2WebEnabled: true,
  });
  m.getElement = () => el;
  m.getAVStream = () => el;
  m.handlerEventListenerStream = () => {};
  m.updateStreamInfo = () => {};
  m.streamListenerBind = () => 1;
  m.player = 'rtsp2web_mse';
  m.select_rtsp2web('default');
  return seen[0];
}

console.log('MonitorStream RTSP2Web MSE url');

test('https server gets a wss websocket', () => {
  assert.ok(mseUrlFor('https://cam.example.com:8083').startsWith('wss://'));
});

test('http server gets a ws websocket', () => {
  assert.ok(mseUrlFor('http://cam.example.com:8083').startsWith('ws://'));
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
