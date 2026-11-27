'use strict';

// The event view remembered the video scale per monitor but not the PanZoom
// zoom: every event started back at 1x, so reviewing the same detail across a
// camera's events meant zooming in again each time. The zoom level is now kept
// in zmEventZoom<monitor id>, like zmEventScale, and restored when the player
// is set up.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(file, 'utf8');

function extract(name) {
  const m = src.match(new RegExp('^function ' + name + '\\(.*?^}', 'ms'));
  return m ? m[0] : '';
}

function load(opts) {
  opts = opts || {};
  const state = {cookies: {}};
  const calls = [];
  const panZoom = {
    zoom: (scale, zoomOpts) => calls.push({scale, opts: zoomOpts}),
  };
  const sandbox = {
    $j: () => ({html: () => {}}),
    eventData: {MonitorId: opts.monitorId === undefined ? 5 : opts.monitorId},
    pageMonitorId: null,
    getCookie: (name) => (name in state.cookies ? state.cookies[name] : null),
    setCookie: (name, value) => {
      state.cookies[name] = value;
    },
    zmPanZoom: {panZoom: {}},
    panZoomEnabled: opts.panZoomEnabled === undefined ? true : opts.panZoomEnabled,
  };
  sandbox.zmPanZoom.panZoom[sandbox.eventData.MonitorId] = panZoom;
  vm.createContext(sandbox);
  const writes = [];
  const setCookie = sandbox.setCookie;
  sandbox.setCookie = (name, value) => {
    writes.push(name);
    setCookie(name, value);
  };
  vm.runInContext('let storedEventZoom = null;', sandbox);
  vm.runInContext(extract('domMonitorId'), sandbox);
  vm.runInContext(extract('storeEventZoom'), sandbox);
  vm.runInContext(extract('restoreEventZoom'), sandbox);
  vm.runInContext(extract('panZoomEventPanzoomchange'), sandbox);
  return {sandbox, state, calls, writes};
}

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

test('changing the zoom stores it for that monitor', () => {
  const env = load();
  assert.strictEqual(typeof env.sandbox.panZoomEventPanzoomchange, 'function');
  env.sandbox.panZoomEventPanzoomchange({detail: {x: 0, y: 0, scale: 1.3498588075760032}});
  assert.strictEqual(env.state.cookies.zmEventZoom5, '1.3');
  assert.strictEqual(env.sandbox.getCookie('zmEventZoom5'), '1.3');
});

test('panning at the same zoom does not write the cookie again', () => {
  const env = load();
  env.sandbox.panZoomEventPanzoomchange({detail: {x: 0, y: 0, scale: 2}});
  env.sandbox.panZoomEventPanzoomchange({detail: {x: 40, y: 10, scale: 2}});
  env.sandbox.panZoomEventPanzoomchange({detail: {x: 80, y: 20, scale: 2}});
  assert.deepStrictEqual(env.writes, ['zmEventZoom5']);
});

test('a stored zoom is applied without animation on init', () => {
  const env = load();
  env.state.cookies.zmEventZoom5 = '2.5';
  assert.strictEqual(typeof env.sandbox.restoreEventZoom, 'function');
  env.sandbox.restoreEventZoom();
  assert.strictEqual(env.calls.length, 1);
  assert.strictEqual(env.calls[0].scale, 2.5);
  assert.strictEqual(env.calls[0].opts.animate, false);
});

test('a stored 1 or no cookie applies nothing', () => {
  const one = load();
  one.state.cookies.zmEventZoom5 = '1';
  one.sandbox.restoreEventZoom();
  assert.strictEqual(one.calls.length, 0);

  const none = load();
  none.sandbox.restoreEventZoom();
  assert.strictEqual(none.calls.length, 0);
});

test('nothing is restored when pan and zoom is off', () => {
  const env = load({panZoomEnabled: false});
  env.state.cookies.zmEventZoom5 = '2.5';
  env.sandbox.restoreEventZoom();
  assert.strictEqual(env.calls.length, 0);
});

test("another monitor's cookie is not used", () => {
  const env = load();
  env.state.cookies.zmEventZoom7 = '3.0';
  env.sandbox.restoreEventZoom();
  assert.strictEqual(env.calls.length, 0);
});

test('initPage restores the zoom after the player is sized', () => {
  const body = extract('initPage');
  const sized = body.indexOf('changeScale();');
  const restored = body.indexOf('restoreEventZoom();');
  assert.ok(sized > -1, 'initPage should size the player');
  assert.ok(restored > sized, 'initPage should restore the zoom after changeScale()');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
