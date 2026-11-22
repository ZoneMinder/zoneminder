'use strict';

// The buffer level span is rendered as #levelValue<id>, but the status handler
// looked up #levelValue, so the level was never written.

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

test('status reply writes the buffer level into #levelValue<id>', () => {
  const written = {};
  const jq = (sel) => ({
    length: sel === '#levelValue7' ? 1 : 0,
    text: (v) => {
      if (v !== undefined) written[sel] = v;
      return '';
    },
    removeClass() {}, addClass() {}, hasClass: () => false,
  });
  const globals = {
    Object, Math, Array, JSON, Set, Map, Date, parseInt, String, Number,
    $j: Object.assign(jq, {ajaxSetup() {}}), $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
    getCookie: () => 'true',
    zmAuth: {update: () => false},
    canEdit: {Monitors: false},
    STATE_IDLE: 0, secsToTime: () => '',
    stateStrings: [], ZM_WEB_SOUND_ON_ALARM: 0, ZM_WEB_POPUP_ON_ALARM: 0,
    setTimeout: () => 0, clearTimeout() {}, setInterval: () => 0,
    clearInterval() {}, statusRefreshTimeout: 1000,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  const m = new globals.MonitorStream({
    id: 7, name: 't', connKey: '1', url: 'i', url_to_zms: 'z', width: 640, height: 480,
  });
  m.getElement = () => ({});
  m.started = true;
  m.streamCmdTimer = 1;
  m.getStreamCmdResponse({result: 'Ok', status: {
    fps: 5, analysisfps: 1, capturefps: 5, level: 42, state: 0,
    delay: 0, zoom: '1.0',
  }});
  assert.strictEqual(written['#levelValue7'], 42);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
