'use strict';

// A go2rtc stream that plays again after an error must clear its error count.
// MonitorStream sets activePlayer to plain 'go2rtc' for every go2rtc mode, and
// video-stream.js passes that to resetCountStreamErrors() when playback starts.
// No playerPriority name is contained in 'go2rtc', so nothing was reset and the
// second error hours later, with a healthy stream in between, reached the
// limit and stopped the stream.

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

function load() {
  const globals = {
    $j: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
    getCookie: () => 'true',
    statusRefreshTimeout: 1000,
    Object, Array, JSON, Math, parseInt, Number,
    setTimeout, clearTimeout, setInterval, clearInterval,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  return globals;
}

function makeMonitor() {
  return new (load().MonitorStream)({
    id: 1, name: 'test', connKey: '123456', url: '',
    url_to_zms: '/zms?monitor=1&mode=jpeg', width: 640, height: 480,
  });
}

console.log('MonitorStream go2rtc error count reset');

test('playing resets the count of the go2rtc mode that failed', () => {
  const monitor = makeMonitor();
  monitor.player = 'go2rtc_webrtc';
  monitor.streamErrorRegistration();
  assert.strictEqual(monitor.getCountStreamErrors('go2rtc_webrtc'), 1);
  monitor.activePlayer = 'go2rtc';
  monitor.resetCountStreamErrors(monitor.activePlayer);
  assert.strictEqual(monitor.getCountStreamErrors('go2rtc_webrtc'), 0,
      'error count survives a successful go2rtc playback');
});

test('every go2rtc mode is reset, other players are not', () => {
  const monitor = makeMonitor();
  for (const p of ['go2rtc_webrtc', 'go2rtc_mse', 'go2rtc_hls', 'zms']) {
    monitor.player = p;
    monitor.streamErrorRegistration();
  }
  monitor.resetCountStreamErrors('go2rtc');
  assert.strictEqual(monitor.getCountStreamErrors('go2rtc_webrtc'), 0);
  assert.strictEqual(monitor.getCountStreamErrors('go2rtc_mse'), 0);
  assert.strictEqual(monitor.getCountStreamErrors('go2rtc_hls'), 0);
  assert.strictEqual(monitor.getCountStreamErrors('zms'), 1);
});

test('two errors with a good playback between do not reach the limit', () => {
  const monitor = makeMonitor();
  monitor.player = 'go2rtc_mse';
  monitor.streamErrorRegistration();
  monitor.activePlayer = 'go2rtc';
  monitor.resetCountStreamErrors(monitor.activePlayer);
  monitor.streamErrorRegistration();
  assert.ok(monitor.getCountStreamErrors(monitor.player) < monitor.limitCountErrors,
      'the second, non-consecutive error stops the stream');
});

test('named players are still reset as before', () => {
  const monitor = makeMonitor();
  monitor.player = 'rtsp2web_hls';
  monitor.streamErrorRegistration();
  monitor.resetCountStreamErrors('rtsp2web_hls');
  assert.strictEqual(monitor.getCountStreamErrors('rtsp2web_hls'), 0);
  monitor.player = 'zms';
  monitor.streamErrorRegistration();
  monitor.resetCountStreamErrors('zms');
  assert.strictEqual(monitor.getCountStreamErrors('zms'), 0);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
