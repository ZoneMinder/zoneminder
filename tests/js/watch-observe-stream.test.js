'use strict';

// The watch page's ResizeObserver was attached once to the stream element that
// existed at load. A monitor switch replaces the element's HTML and the go2rtc,
// rtsp2web and janus players replace the element itself, after which resizes
// were no longer noticed and the stream was not rescaled.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {loadWatch, makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js ResizeObserver');

test('observeStreamElements observes whatever stream elements exist now', () => {
  let els = [{id: 'liveStream1'}, {id: 'monitorStatus1'}];
  const g = loadWatch({
    document: {
      getElementById: () => null, querySelector: () => null,
      querySelectorAll: () => els, addEventListener: () => {},
      createElement: () => ({}),
    },
  });
  const seen = [];
  g.observer = {observe: (el) => seen.push(el.id)};
  g.observeStreamElements();
  els = [{id: 'liveStream2'}, {id: 'monitorStatus2'}];
  g.observeStreamElements();
  assert.deepStrictEqual(seen,
      ['liveStream1', 'monitorStatus1', 'liveStream2', 'monitorStatus2']);
});

test('observeStreamElements does nothing before the observer exists', () => {
  const g = loadWatch();
  g.observeStreamElements();
});

test('MonitorStream tells the page when it replaces its element', () => {
  const src = fs.readFileSync(
      path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');
  const el = {nodeName: 'IMG', src: '', setAttribute() {}, getAttribute: () => null};
  let calls = 0;
  const globals = {
    Object, Math, Array, JSON, Set, Map, Date, parseInt, String, Number,
    $j: makeChainable(), $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch', getCookie: () => 'true',
    replaceDOMElement: () => el, generateUUID: () => 'u',
    observeStreamElements: () => calls++,
    zmAuth: {hash: '', applyTo: (u) => u, appendTo: (u) => u},
    document: {getElementById: () => null, createElement: () => ({})},
    setInterval: () => 1, clearInterval() {}, setTimeout: () => 0, clearTimeout() {},
    statusRefreshTimeout: 1000,
    manageEventListener: {addEventListener: () => 1, removeEventListener: () => null},
    hideAudioMotion() {},
    streamSessionActive: () => true,
    jQuery: {ajaxQueue: () => ({})},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  const m = new globals.MonitorStream({
    id: 5, name: 't', connKey: '1', url: 'i', url_to_zms: '/zms?x=1&mode=single',
    width: 640, height: 480,
  });
  m.getElement = () => el;
  m.destroyVolumeSlider = () => {};
  m.updateStreamInfo = () => {};
  m.streamListenerBind = () => 1;
  m.select_zms();
  assert.strictEqual(calls, 1);
});

done();
