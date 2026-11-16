'use strict';

// A monitor with go2rtc enabled and its player left on Auto tried go2rtc first,
// and with ZM_GO2RTC_PATH empty select_go2rtc() alerted "ZM_GO2RTC_PATH is empty"
// on every page that showed it, montage included, although nobody had asked for
// go2rtc. Under Auto an unconfigured go2rtc is now skipped for the next player;
// the alert stays for a monitor or browser that chose go2rtc explicitly.

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

function loadMonitorStream(go2rtcPath) {
  const globals = {
    ZM_GO2RTC_PATH: go2rtcPath,
    parseInt, Number, Object, Math, String, Array, JSON,
    alerts: [],
    alert(msg) {
      this.alerts.push(msg);
    },
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
    getCookie: () => 'true',
    setCookie: () => {},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
  };
  const sandbox = new Proxy(globals, {
    has: () => true, // make every bare identifier resolve against this object
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  assert.strictEqual(typeof globals.MonitorStream, 'function',
      'MonitorStream.js did not define MonitorStream');
  return globals;
}


// Build a go2rtc-enabled monitor and record which player selectPlayer() starts.
function select(go2rtcPath, defaultPlayer) {
  const sandbox = loadMonitorStream(go2rtcPath);
  const monitor = new sandbox.MonitorStream({
    id: 2, name: 'test', connKey: null, url: '', url_to_zms: '',
    width: 640, height: 480, Go2RTCEnabled: true, DefaultPlayer: defaultPlayer,
  });
  const started = [];
  monitor.isActive = true;
  monitor.select_go2rtc = () => started.push('go2rtc');
  monitor.select_janus = () => started.push('janus');
  monitor.select_rtsp2web = () => started.push('rtsp2web');
  monitor.select_zms = () => started.push('zms');
  // selectNextPlayer() switches by setting this.player and restarting, and
  // restart() comes back through selectPlayer() with the new player.
  let depth = 0;
  monitor.restart = () => {
    started.push('restart:' + monitor.player);
    if (++depth < 10) monitor.selectPlayer('default');
  };
  monitor.selectPlayer('default');
  return {started, alerts: sandbox.alerts};
}

console.log('MonitorStream player selection with go2rtc unconfigured');

test('Auto with no ZM_GO2RTC_PATH does not try go2rtc or alert', () => {
  const {started, alerts} = select('', '');
  assert.ok(!started.includes('go2rtc'), 'started go2rtc: ' + started.join(','));
  assert.deepStrictEqual(alerts, []);
  assert.ok(started.includes('zms'), 'did not fall back to zms: ' + started.join(','));
});

test('go2rtc chosen for the monitor still tries go2rtc', () => {
  const {started} = select('', 'go2rtc');
  assert.deepStrictEqual(started, ['go2rtc']);
});

test('Auto with ZM_GO2RTC_PATH set uses go2rtc', () => {
  const {started, alerts} = select('http://localhost:1984', '');
  assert.ok(started.includes('go2rtc'), 'did not start go2rtc: ' + started.join(','));
  assert.deepStrictEqual(alerts, []);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
