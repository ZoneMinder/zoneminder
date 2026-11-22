'use strict';

// On the zms path the event view can move on to another monitor's event
// without a page load (refs #4186). The page's elements and PanZoom are still
// those of the monitor it was loaded for, and its image was sized for that
// monitor's frames.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');

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

// Load event.js for an event of monitor 1, recording what is done to the
// zones overlay and which of the resize paths runs.
function load(zonesVisible) {
  const state = {zones: [], calls: [], visible: zonesVisible};
  const button = {disabled: false};
  const $j = function(selector) {
    if (selector === '#zones1') {
      return {
        is: () => state.visible,
        hide: () => {
          state.zones.push('hide');
          state.visible = false;
        },
        show: () => {
          state.zones.push('show');
          state.visible = true;
        },
      };
    }
    return makeChainable();
  };
  const globals = {
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: (id) => (id === 'toggleZonesButton' ? button : null),
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    eventData: {Id: 10, MonitorId: '1', Width: 640, Height: 480},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  globals.changeScale = () => state.calls.push('changeScale');
  globals.drawProgressBar = () => state.calls.push('drawProgressBar');
  // The page's elements are looked up once it has loaded.
  assert.strictEqual(globals.domMonitorId(), '1');
  return {globals, state, button};
}

test('element ids stay those of the monitor the page was loaded for', () => {
  // PanZoom and the stream element are keyed by this id; looking them up by
  // the new event's monitor found nothing, and the scale timer threw.
  const {globals} = load();
  globals.eventData = {Id: 11, MonitorId: '2', Width: 640, Height: 480};
  assert.strictEqual(globals.domMonitorId(), '1');
});

test('an event with frames of another size resizes the image', () => {
  const {globals, state} = load();
  const previous = globals.eventData;
  globals.eventData = {Id: 11, MonitorId: '2', Width: 1920, Height: 1080};
  globals.zmsEventChanged(previous);
  assert.deepStrictEqual(state.calls, ['changeScale']);
});

test('an event with frames of the same size only redraws the progress bar', () => {
  const {globals, state} = load();
  const previous = globals.eventData;
  globals.eventData = {Id: 11, MonitorId: '1', Width: 640, Height: 480};
  globals.zmsEventChanged(previous);
  assert.deepStrictEqual(state.calls, ['drawProgressBar']);
});

test('the zones are hidden over another monitor and restored on return', () => {
  const {globals, state, button} = load(true);
  const first = globals.eventData;
  globals.eventData = {Id: 11, MonitorId: '2', Width: 640, Height: 480};
  globals.zmsEventChanged(first);
  assert.deepStrictEqual(state.zones, ['hide']);
  assert.strictEqual(button.disabled, true);

  const second = globals.eventData;
  globals.eventData = {Id: 12, MonitorId: '1', Width: 640, Height: 480};
  globals.zmsEventChanged(second);
  assert.deepStrictEqual(state.zones, ['hide', 'show']);
  assert.strictEqual(button.disabled, false);
});

test('zones the user had hidden stay hidden on return', () => {
  const {globals, state} = load(false);
  const first = globals.eventData;
  globals.eventData = {Id: 11, MonitorId: '2', Width: 640, Height: 480};
  globals.zmsEventChanged(first);
  const second = globals.eventData;
  globals.eventData = {Id: 12, MonitorId: '1', Width: 640, Height: 480};
  globals.zmsEventChanged(second);
  assert.deepStrictEqual(state.zones, ['hide']);
});

test('two events of another monitor in a row still restore the zones', () => {
  const {globals, state} = load(true);
  let previous = globals.eventData;
  for (const next of [{Id: 11, MonitorId: '2'}, {Id: 12, MonitorId: '3'}, {Id: 13, MonitorId: '1'}]) {
    globals.eventData = Object.assign({Width: 640, Height: 480}, next);
    globals.zmsEventChanged(previous);
    previous = globals.eventData;
  }
  assert.strictEqual(state.visible, true);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
