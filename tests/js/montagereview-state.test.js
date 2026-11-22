'use strict';

// Montage review state and drawing bugs, each reachable from the page:
//
// 1. Alarm frames on the timeline were drawn with a NaN/-Infinity alpha
//    because maxScore is emitted as 0 and never updated, and frames whose
//    Score is the string "0" (the API returns strings) were drawn as alarms.
// 2. Live mode with no events in range never refreshed the images, because
//    outputUpdate() and wait_for_events() both wait for an event to exist.
// 3. An image staged while another was loading was dropped, so after
//    scrubbing at speed 0 (no timer running) the canvas showed an older time
//    than the slider.
// 4. Scrubbing the timeline by touch did nothing: a TouchEvent has no pageX.
// 5. Playback reaching the end of the range set the speed to 0 but left the
//    speed slider on the old value.
// 6. "No event" was written over the previous frame instead of replacing it.
// 7. initPage() paired monitorPtr[i] with monitorData[i], but monitorData
//    skips deleted monitors, so the streams got another monitor's URLs.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js'), 'utf8');

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

function makeCtx() {
  const ctx = {
    calls: [],
    _alpha: 1,
    set globalAlpha(v) {
      // Canvas ignores non-finite and out of range values.
      if (Number.isFinite(v) && v >= 0 && v <= 1) this._alpha = v;
    },
    get globalAlpha() {
      return this._alpha;
    },
    measureText: () => ({width: 10}),
  };
  for (const m of ['fillRect', 'clearRect', 'fillText', 'drawImage', 'strokeRect', 'putImageData']) {
    ctx[m] = function() {
      ctx.calls.push({m, alpha: ctx._alpha, args: Array.from(arguments)});
    };
  }
  return ctx;
}

function load(extra) {
  const elements = {};
  const el = (id) => elements[id] || (elements[id] = {
    id, style: {}, addEventListener() {}, innerHTML: '', offsetWidth: 0,
  });
  const proxy = new Proxy(function() {}, {
    get: (t, p) => (p === 'then' ? undefined : (p === 'length' ? 0 : proxy)),
    apply: () => proxy,
  });
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {getElementById: el, createElement: () => ({style: {}}), body: {}},
    $j: Object.assign(function() {
      return proxy;
    }, {ajax: () => proxy}),
    HTMLCanvasElement: function() {}, Image: function() {},
    Math, Date, Object, Promise, parseInt, parseFloat, Array, Number, isNaN,
    setInterval: () => 1, clearInterval() {}, setTimeout: () => 1,
    setCookie() {}, secs2dbstr: (s) => String(s), secs2inputstr: (s) => String(s),
    zmAuth: {applyTo: (u) => u, appendTo: (u) => u},
    addOrUpdateRandParam: (u) => u + '?r=1',
    navbar_type: 'left',
    elements,
  };
  sandbox.window = sandbox;
  sandbox.window.addEventListener = () => {};
  vm.createContext(sandbox);
  vm.runInContext(`
    var currentScale=1, liveMode=0, fitMode=1, currentSpeed=1, speedIndex=5, lastSpeedIndex=0;
    var currentDisplayInterval=100, timerInterval, timerObj, freeTimeLastIntervals=[];
    var imageLoadTimesEvaluated=0, imageLoadTimesNeeded=15, timeLabelsFractOfRow=0.9;
    const events = {}; const events_for_monitor = {};
    var maxScore=0;
    var monitorName=[], monitorLoading=[], monitorServerId=[], monitorImageObject=[], monitorImageURL=[],
      monitorLoadingStageURL=[], monitorLoadStartTimems=[], monitorLoadEndTimems=[], monitorColour=[],
      monitorWidth=[], monitorHeight=[], monitorIndex=[], monitorNormalizeScale=[], monitorZoomScale=[],
      monitorCanvasObj=[], monitorCanvasCtx=[], monitorPtr=[];
    var numMonitors=0, minTimeSecs=1000, maxTimeSecs=2000, rangeTimeSecs=1001, currentTimeSecs=1000;
    var speeds=[0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];
    var cWidth=1000, cHeight=100, rowHeight=10, canvas, ctx=null, underSlider;
    var monitorData=[], Servers={}, serverId=0, Storage=[], thisUrl='', currentView='montagereview';
  `, sandbox);
  vm.runInContext(src, sandbox);
  if (extra) vm.runInContext(extra, sandbox);
  return sandbox;
}
const run = (sb, code) => vm.runInContext(code, sb);

// 1 ---------------------------------------------------------------------
test('alarm frame is drawn with a usable alpha even when maxScore starts at 0', () => {
  const sb = load();
  const c = makeCtx();
  sb.ctx = c;
  run(sb, `
    events[7] = {Id: 7, MonitorId: 1};
    monitorColour[1] = '#fff'; monitorIndex[1] = 0;
    loadFramesDone = false;`);
  run(sb, `
    var f = {EventId: 7, FrameId: 3, TimeStampSecs: '1500', Score: '40'};
    events[7].FramesById = []; events[7].FramesById[3] = f;
    ctx.globalAlpha = 0.2;
    drawFrameOnGraph(f);`);
  const fill = c.calls.find((x) => x.m === 'fillRect');
  assert.ok(fill, 'frame was drawn');
  assert.ok(fill.alpha >= 0.4, 'alpha ' + fill.alpha + ' should be the alarm alpha, not the 0.2 background');
});

test('loadFrames records the highest frame score in maxScore', () => {
  const sb = load();
  sb.$j.ajax = (url, opts) => {
    opts.success({frames: [
      {Frame: {EventId: 7, FrameId: 1, TimeStampSecs: '1500', Score: '0'}},
      {Frame: {EventId: 7, FrameId: 2, TimeStampSecs: '1501', Score: '63'}},
    ]});
  };
  run(sb, 'events[7] = {Id: 7, MonitorId: 1, EndDateTime: "x"}; Servers[0] = {urlToApi: () => "/api"};');
  run(sb, 'loadFrames({7: events[7]})');
  assert.strictEqual(run(sb, 'maxScore'), 63);
});

test('a frame whose Score is the string "0" is not drawn as an alarm', () => {
  const sb = load();
  const c = makeCtx();
  sb.ctx = c;
  run(sb, `events[7] = {Id: 7, MonitorId: 1}; monitorColour[1] = '#fff'; monitorIndex[1] = 0; maxScore = 50;
    drawFrameOnGraph({EventId: 7, FrameId: 3, TimeStampSecs: '1500', Score: '0'});`);
  assert.strictEqual(c.calls.filter((x) => x.m === 'fillRect').length, 0);
});

// 2 ---------------------------------------------------------------------
function oneMonitor(sb) {
  const c = makeCtx();
  sb.loads = [];
  run(sb, `
    numMonitors = 1; monitorPtr[0] = 1; monitorName[1] = 'Cam';
    monitorImageObject[1] = {src: 'old'}; monitorImageURL[1] = 'live';
    monitorLoading[1] = false;
    monitorCanvasObj[1] = {width: 100, height: 100};
    drawSliderOnGraph = function() {};`);
  sb.monitorCanvasCtx = [null, c];
  run(sb, 'monitorCanvasCtx = window.monitorCanvasCtx;');
  return c;
}

test('live mode refreshes images when there are no events', () => {
  const sb = load();
  oneMonitor(sb);
  run(sb, 'liveMode = 1;');
  run(sb, 'outputUpdate(currentTimeSecs)');
  assert.notStrictEqual(run(sb, 'monitorImageObject[1].src'), 'old', 'live image was not requested');
});

test('wait_for_events starts the timer in live mode with no events', () => {
  const sb = load();
  oneMonitor(sb);
  run(sb, 'liveMode = 1; timerFire = function() { window.fired = true; };');
  run(sb, 'wait_for_events()');
  assert.ok(sb.fired, 'timerFire was not called');
});

// 3 ---------------------------------------------------------------------
test('image staged while another loads is loaded when the first finishes', () => {
  const sb = load();
  oneMonitor(sb);
  run(sb, `monitorImageObject[1] = {src: 'a'}; monitorLoading[1] = true;`);
  run(sb, 'loadImage2Monitor(1, "b"); imagedone(monitorImageObject[1], 1, true);');
  assert.strictEqual(run(sb, 'monitorImageObject[1].src'), 'b', 'the staged image was dropped');
});

// 4 ---------------------------------------------------------------------
test('relMouseCoords reads the position of a touch', () => {
  const sb = load();
  const target = {offsetLeft: 10, offsetTop: 0, scrollLeft: 0, scrollTop: 0, offsetParent: null};
  const touch = {target, touches: [{pageX: 110, pageY: 5}]};
  const r = run(sb, 'relMouseCoords').call(null, touch);
  assert.strictEqual(r.x, 100);
});

// 5 ---------------------------------------------------------------------
test('playback reaching the end of the range moves the speed slider to 0', () => {
  const sb = load();
  oneMonitor(sb);
  const slider = {value: 5};
  sb.$j = Object.assign(function(sel) {
    if (sel === '#speedslider') return {val: (v) => (slider.value = v), 0: slider};
    return new Proxy(function() {}, {get: (t, p) => (p === 'then' ? undefined : () => 0), apply: () => 0});
  }, {ajax() {}});
  run(sb, `$j = window.$j; currentTimeSecs = 1999.99; maxTimeSecs = 2000; currentSpeed = 1; speedIndex = 5;
    lastTimerFireMs = Date.now() - 5000;`);
  run(sb, 'timerFire()');
  assert.strictEqual(run(sb, 'currentSpeed'), 0);
  assert.strictEqual(slider.value, 0, 'slider still shows ' + slider.value);
});

// 6 ---------------------------------------------------------------------
test('writeText clears the previous frame first', () => {
  const sb = load();
  const c = oneMonitor(sb);
  run(sb, 'writeText(1, "No event")');
  const clear = c.calls.findIndex((x) => x.m === 'clearRect');
  const text = c.calls.findIndex((x) => x.m === 'fillText');
  assert.ok(clear >= 0 && clear < text, 'canvas not cleared before the text');
});

// 7 ---------------------------------------------------------------------
test('initPage gives each stream its own monitor URLs when monitorData skips a monitor', () => {
  const sb = load();
  const made = [];
  sb.EventStream = function(o) {
    made.push(o);
    this.setScale = () => {};
  };
  run(sb, `
    numMonitors = 2; monitorPtr = [1, 2];
    monitorData = [{Id: 2, Url: 'url2', UrlToZMS: 'zms2'}];
    Servers[0] = {PathToZMS: '/zms'};
    monitorServerId[1] = '0'; monitorServerId[2] = '0';
    monitorWidth[1] = 100; monitorWidth[2] = 100; monitorHeight[1] = 100; monitorHeight[2] = 100;
    liveMode = 0; drawGraph = function() {};`);
  sb.document.getElementById = (id) => ({
    id, style: {}, addEventListener() {}, getContext: () => makeCtx(), width: 100, height: 100,
    offsetWidth: 0,
  });
  try {
    run(sb, 'initPage()');
  } catch (e) {
    if (process.env.DBG) console.error(e);
  }
  const s1 = made.find((o) => o.monitorId == 1);
  const s2 = made.find((o) => o.monitorId == 2);
  assert.ok(s1 && s2, 'streams were not created');
  assert.notStrictEqual(s1.url, 'url2', 'monitor 1 got monitor 2 urls');
  assert.strictEqual(s2.url, 'url2');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
