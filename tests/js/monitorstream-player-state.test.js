'use strict';

// Three pieces of MonitorStream state that went stale:
//  - the 'pause' listener removes the volumechange listener but kept its id, so
//    createVolumeSlider() on the next 'play' believed it was still registered;
//  - selectNextPlayer() gave up with "All players failed" and left `starting`
//    set, so start() (the Play button) was ignored from then on;
//  - setMaxFPS() sent a zms command for go2rtc/rtsp2web/janus streams, which
//    have no zms and so answer no_socket.

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

// Same contract as ManageEventListener in skin.js: add returns an id, remove
// returns the id when something was removed and null otherwise.
function makeListenerManager() {
  const listeners = {};
  let idx = 1;
  return {
    listeners,
    addEventListener(el, type, fn, opts = {}) {
      if (opts && opts.replaceId != null) delete listeners[opts.replaceId];
      const id = idx++;
      listeners[id] = {el, type, fn};
      return id;
    },
    removeEventListener(id) {
      const l = listeners[id];
      delete listeners[id];
      return l ? id : null;
    },
    fire(type) {
      Object.values(listeners).filter((l) => l.type === type).forEach((l) => l.fn({}));
    },
    count(type) {
      return Object.values(listeners).filter((l) => l.type === type).length;
    },
  };
}

function load() {
  const manageEventListener = makeListenerManager();
  const globals = {
    $j: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
    getCookie: () => 'true',
    document: {getElementById: () => null, querySelector: () => null},
    statusRefreshTimeout: 1000,
    CMD_MAXFPS: 7, CMD_PLAY: 1,
    manageEventListener,
    streamSessionActive: () => true,
    getTracksFromStream: () => {},
    getVolumeSlider: () => ({noUiSlider: {}}),
    generateUUID: () => 'uuid',
    ZM_GO2RTC_PATH: '',
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

function makeMonitor(globals) {
  const monitor = new globals.MonitorStream({
    id: 1, name: 'test', connKey: '123456', url: '',
    url_to_zms: '/zms?monitor=1&mode=jpeg', width: 640, height: 480,
  });
  monitor.writeTextInfoBlock = () => {};
  monitor.sent = [];
  monitor.streamCmdReq = (p) => monitor.sent.push(p);
  return monitor;
}

console.log('MonitorStream player state');

test('volumechange is registered again when play follows pause', () => {
  const globals = load();
  const monitor = makeMonitor(globals);
  const video = {};
  monitor.getAVStream = () => video;
  monitor.getElement = () => video;
  monitor.handlerEventListenerStream(video);

  globals.manageEventListener.fire('playing');
  assert.strictEqual(globals.manageEventListener.count('volumechange'), 1);
  globals.manageEventListener.fire('pause');
  assert.strictEqual(globals.manageEventListener.count('volumechange'), 0);
  globals.manageEventListener.fire('playing');
  assert.strictEqual(globals.manageEventListener.count('volumechange'), 1,
      'volume and mute changes are no longer followed after pause/play');
});

test('start() is accepted again after every player has failed', () => {
  const globals = load();
  const monitor = makeMonitor(globals);
  monitor.player = 'zms';
  monitor.starting = true;
  for (const key in monitor.playerPriority) monitor.playerPriority[key].countErrors = 1;
  monitor.selectNextPlayer();
  assert.strictEqual(monitor.fatalError, true);
  assert.strictEqual(monitor.starting, false, 'starting stays set, start() is ignored');
});

test('setMaxFPS sends nothing for a non-zms player', () => {
  const globals = load();
  const monitor = makeMonitor(globals);
  monitor.started = true;
  monitor.activePlayer = 'go2rtc';
  monitor.setMaxFPS(5);
  assert.strictEqual(monitor.sent.length, 0, 'zms command sent to a stream without zms');
});

test('setMaxFPS still reaches zms', () => {
  const globals = load();
  const monitor = makeMonitor(globals);
  monitor.started = true;
  monitor.activePlayer = 'zms';
  monitor.setMaxFPS(5);
  assert.strictEqual(monitor.sent.length, 1);
  assert.strictEqual(monitor.sent[0].command, 7);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
