'use strict';

// select_zms() has three ways out: resume an existing zms with CMD_PLAY, send
// CMD_PLAY to the one the page was rendered with in mode=paused, or build a new
// one. The first two send a stream command before the tail of the function sets
// `started`, and streamCommand() silently drops anything sent while that is
// false — so both could report success while sending nothing, leaving the
// picture frozen on the last keepalive frame.
//
// These assert what actually reached the wire, not just which branch ran.
// refs #4706

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

// MonitorStream.js is a browser file that leans on globals from skin.js and the
// view templates. Resolve unknown globals to undefined and give real values only
// to the handful these tests depend on.
function loadMonitorStream(authHash) {
  const globals = {
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
    statusRefreshTimeout: 1000,
    CMD_PLAY: 1,
    CMD_QUIT: 17,
    // '' is what an install with auth off, or under the plain/none relay forms,
    // actually has here.
    zmAuth: {hash: authHash, applyTo: (url) => url},
    authHashFromRelay: () => authHash,
    streamSessionActive: () => true,
    generateUUID: () => 'uuid',
    hideAudioMotion: () => {},
    // select_zms() swaps the element first; hand back the one the test set up.
    replaceDOMElement: (existing) => existing,
    // The sandbox proxy answers every bare identifier, so the builtins the code
    // uses have to be present on it explicitly.
    Object: Object,
    Array: Array,
    JSON: JSON,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  assert.strictEqual(typeof globals.MonitorStream, 'function',
      'MonitorStream.js did not define MonitorStream');
  return globals;
}

// select_zms() replaces the DOM element before anything else, so the monitor has
// to hand back a stand-in <img> whose src the branch conditions can read.
function makeMonitor(sandbox, {src: imgSrc, connKey, activePlayer, stoppedPlayer}) {
  const monitor = new sandbox.MonitorStream({
    id: 1, name: 'test', connKey: connKey || null,
    url: '', url_to_zms: '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg',
    width: 640, height: 480,
  });
  const element = {nodeName: 'IMG', src: imgSrc, srcObject: null,
    getAttribute: () => 'eager', setAttribute: () => {}};

  monitor.element = element;
  monitor.getElement = () => element;
  monitor.connKey = connKey || null;
  monitor.streamCmdParms = {connkey: connKey || null};
  monitor.statusCmdParms = {connkey: connKey || null};
  monitor.activePlayer = activePlayer || '';
  monitor.stoppedPlayer = stoppedPlayer || '';
  monitor.started = false;
  monitor.isActive = true;
  monitor.scale = 100;

  // Record what reaches the wire rather than which branch was taken.
  monitor.sent = [];
  monitor.streamCmdReq = (params) => monitor.sent.push(params);
  monitor.quit = [];
  monitor.quitConnKey = (key) => monitor.quit.push(key);

  monitor.destroyVolumeSlider = () => {};
  monitor.updateStreamInfo = () => {};
  monitor.writeTextInfoBlock = () => {};
  monitor.streamListenerBind = () => () => {};
  monitor.streamCmdQuery = () => {};
  monitor.resetCountStreamErrors = () => {};
  monitor.img_onerror = () => {};
  monitor.img_onload = () => {};
  monitor.genConnKey = () => 999999;
  monitor.kill = () => {};

  return {monitor, element};
}

const playCommands = (monitor) =>
  monitor.sent.filter((p) => p.command === 1); // CMD_PLAY

console.log('MonitorStream.select_zms() resume and initial-paused paths');

test('a stopped zms is resumed with CMD_PLAY on its existing connkey', () => {
  // stop() clears activePlayer and started, so the resume branch is reached
  // through stoppedPlayer with the connkey still held.
  const sandbox = loadMonitorStream('');
  const {monitor, element} = makeMonitor(sandbox, {
    src: '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg&connkey=123456',
    connKey: 123456, stoppedPlayer: 'zms',
  });

  monitor.select_zms();

  const plays = playCommands(monitor);
  assert.strictEqual(plays.length, 1,
      'resume sent ' + plays.length + ' CMD_PLAY, expected exactly 1');
  assert.strictEqual(plays[0].connkey, 123456,
      'CMD_PLAY went to the wrong connkey');
  assert.strictEqual(monitor.connKey, 123456,
      'resume must not replace the connkey that addresses the running zms');
  assert.strictEqual(element.src,
      '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg&connkey=123456',
      'resume must leave src untouched, or the browser tears the stream down');
  assert.deepStrictEqual(monitor.quit, [],
      'resume must not quit the process it is about to resume');
});

test('an initial mode=paused stream is played, not left paused', () => {
  // The regression: streamCommand() drops commands while !started, and the tail
  // of select_zms() sets started only after this branch has already run.
  const sandbox = loadMonitorStream('');
  const {monitor} = makeMonitor(sandbox, {
    src: '/zm/cgi-bin/nph-zms?monitor=1&mode=paused&connkey=222222',
    connKey: 222222,
  });

  monitor.select_zms();

  const plays = playCommands(monitor);
  assert.strictEqual(plays.length, 1,
      'initial mode=paused sent ' + plays.length + ' CMD_PLAY, expected exactly 1');
  assert.strictEqual(plays[0].connkey, 222222,
      'CMD_PLAY went to the wrong connkey');
});

test('with auth off the resume path is taken rather than a rebuild', () => {
  // srcAuthCurrent used to require a non-empty zmAuth.hash, so installs with
  // auth off always rebuilt, abandoning a perfectly good zms every cycle.
  const sandbox = loadMonitorStream('');
  const {monitor, element} = makeMonitor(sandbox, {
    src: '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg&connkey=333333',
    connKey: 333333, activePlayer: 'zms',
  });

  monitor.select_zms();

  assert.strictEqual(monitor.connKey, 333333, 'auth-off install rebuilt the stream');
  assert.strictEqual(element.src,
      '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg&connkey=333333',
      'auth-off install replaced src instead of resuming');
});

test('a stale auth hash still rebuilds, and quits the old zms first', () => {
  // The counterpart: when there is a hash and it no longer matches, the stream
  // has to be rebuilt — and the process the old connkey addressed told to quit,
  // because nothing can reach it once the key is replaced.
  const sandbox = loadMonitorStream('freshhash');
  const {monitor} = makeMonitor(sandbox, {
    src: '/zm/cgi-bin/nph-zms?monitor=1&mode=jpeg&auth=stalehash',
    connKey: 444444, activePlayer: 'zms',
  });
  sandbox.authHashFromRelay = () => 'stalehash';

  monitor.select_zms();

  assert.deepStrictEqual(monitor.quit, [444444],
      'the replaced zms was not told to quit');
  assert.strictEqual(monitor.connKey, 999999, 'rebuild should mint a new connkey');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
