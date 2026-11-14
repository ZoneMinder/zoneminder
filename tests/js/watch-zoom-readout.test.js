'use strict';

// The zoom readout is rendered hidden and the status handler only ever added
// the hidden class, so "Zoom: Nx" never appeared. The zoom-out button state was
// keyed 'zoomOutBtn' in MonitorStream but registered as 'zoomOutButton' with a
// selector missing its '#', so it was never updated either.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {loadWatch, makeChainable, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');

function statusReply(zoom) {
  const classes = {};
  const buttonStates = [];
  const jq = (sel) => ({
    length: 1, text() {
      return '';
    },
    removeClass(c) {
      classes[sel] = 'removed ' + c;
    },
    addClass(c) {
      classes[sel] = 'added ' + c;
    },
    hasClass: () => false,
  });
  const globals = {
    Object, Math, Array, JSON, Set, Map, Date, parseInt, String, Number,
    $j: Object.assign(jq, {ajaxSetup() {}}), $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch', getCookie: () => 'true',
    zmAuth: {update: () => false}, canEdit: {Monitors: false},
    STATE_IDLE: 0, secsToTime: () => '', stateStrings: [],
    ZM_WEB_SOUND_ON_ALARM: 0, ZM_WEB_POPUP_ON_ALARM: 0,
    setButtonState: (id, state) => buttonStates.push(id + ':' + state),
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
  m.setButton('zoomOutBtn', {});
  m.getStreamCmdResponse({result: 'Ok', status: {
    fps: 5, analysisfps: 1, capturefps: 5, level: 0, state: 0, delay: 0, zoom,
  }});
  return {classes, buttonStates};
}

console.log('zoom readout and zoom-out button');

test('the readout is shown when zoomed in and hidden at 1.0', () => {
  assert.strictEqual(statusReply('2.0').classes['#zoom7'], 'removed hidden');
  assert.strictEqual(statusReply('1.0').classes['#zoom7'], 'added hidden');
});

test('the zoom-out button state follows the zoom', () => {
  assert.deepStrictEqual(statusReply('2.0').buttonStates, ['zoomOutBtn:inactive']);
  assert.deepStrictEqual(statusReply('1.0').buttonStates, ['zoomOutBtn:unavail']);
});

test('watch registers the zoom-out button under the key MonitorStream reads', () => {
  const g = loadWatch();
  g.enableAlmBtn = g.forceAlmBtn = g.analyseBtn = makeHandlerSet();
  g.canEdit = {Monitors: false};
  const keys = [];
  g.MonitorStream = function() {
    this.setButton = (name) => keys.push(name);
    this.setup_onclick = this.setup_onmove = this.setup_onpause = () => {};
    this.setup_onplay = this.setup_onalarm = this.manageAvailablePlayers = () => {};
    this.setBottomElement = () => {};
  };
  g.getCookie = () => null;
  g.setChannelStream = g.streamCmdPlay = g.manageStreamQualityVisibility = () => {};
  g.monitorData = [{id: 1}];
  g.monIdx = 0;
  g.panZoomEnabled = false;
  g.streamStart();
  assert.ok(keys.includes('zoomOutBtn'));
  keys.length = 0;
  g.panZoomEnabled = true;
  g.streamStart();
  assert.ok(!keys.includes('zoomOutBtn'), 'the button is hidden with the new ZoomPan');
});

done();
