'use strict';

// RTSP2Web only has channels 0 (primary) and 1 (secondary). The default
// StreamChannel is 'Restream', which was mapped to channel 2, so a default
// RTSP2Web monitor asked for /channel/2/ and got nothing.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = process.argv[2] ||
    path.join(__dirname, '../../web/js/MonitorStream.js');
const src = fs.readFileSync(jsPath, 'utf8');

// MonitorStream builds RTSP2Web URLs with rtsp2webUrl() from skin.js, which
// every page loads before it; load the real one rather than a copy.
const skinSrc = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const rtsp2webUrlSrc =
    skinSrc.match(/function rtsp2webUrl\(base, suffix\) \{[\s\S]*?\n\}\n/);
assert.ok(rtsp2webUrlSrc, 'rtsp2webUrl() not found in skin.js');

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

function mseUrlFor(path2web, channel) {
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
  vm.runInContext(rtsp2webUrlSrc[0], sandbox, {filename: 'skin.js'});
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
  m.select_rtsp2web(channel);
  return seen[0];
}

console.log('MonitorStream RTSP2Web channel mapping');

const base = 'http://cam.example.com:8083';
test('Restream (the default StreamChannel) uses channel 0', () => {
  assert.strictEqual(mseUrlFor(base, 'Restream'),
      'ws://cam.example.com:8083/stream/5/channel/0/mse?uuid=5&channel=0');
});

test('ZoneMinderPrimary uses channel 0', () => {
  assert.ok(mseUrlFor(base, 'ZoneMinderPrimary').includes('/channel/0/'));
});

test('CameraDirectPrimary uses channel 0', () => {
  assert.ok(mseUrlFor(base, 'CameraDirectPrimary').includes('/channel/0/'));
});

test('CameraDirectSecondary uses channel 1', () => {
  assert.ok(mseUrlFor(base, 'CameraDirectSecondary').includes('/channel/1/'));
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
