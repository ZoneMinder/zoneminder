'use strict';

// loadFrames sets zm_event.FramesById = [] as a "loading" marker before
// requesting /frames. If the request failed (ajax error callback, or a
// success callback whose data is {result:'Error'}), the marker used to
// stay set, so a closed event (EndDateTime set) was skipped by the filter
// on every later loadFrames call and its frames could never load again
// until a page reload. On failure the marker must now be removed for that
// batch, so the event is retried. A successful load still fills FramesById
// and a closed event is not re-requested.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.env.MONTAGEREVIEW_SRC ||
    path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js'), 'utf8');

let passed = 0;
let failed = 0;
const tests = [];
function test(name, fn) {
  tests.push({name, fn});
}

function load() {
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
    logAjaxFail: () => {},
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
  return sandbox;
}
const run = (sb, code) => vm.runInContext(code, sb);

// One closed event in the global events map, and an ajax stub that records
// every call and answers each request via respond(opts).
function setup(sb, respond) {
  sb.ajaxCalls = [];
  sb.$j.ajax = function(url, opts) {
    sb.ajaxCalls.push({url, opts});
    respond(opts);
  };
  run(sb, `events[7] = {Id: 7, MonitorId: 1, EndDateTime: "2020-01-01 00:00:00"};
    Servers[0] = {urlToApi: function() { return "/api"; }};`);
}

test('ajax error rejects the promise and removes the loading marker, so the event is retried', async () => {
  const sb = load();
  setup(sb, (opts) => opts.error({}));
  const p = run(sb, 'loadFrames({7: events[7]})');
  await assert.rejects(p, /error/i);
  assert.strictEqual(sb.ajaxCalls.length, 1, 'first call should issue one request');
  assert.strictEqual(run(sb, 'events[7].FramesById'), undefined,
      'the loading marker was not removed after the ajax error');
  const p2 = run(sb, 'loadFrames({7: events[7]})');
  p2.catch(() => {});
  assert.strictEqual(sb.ajaxCalls.length, 2,
      'a second loadFrames did not issue a new request for the closed event');
});

test('success with {result:"Error"} removes the loading marker, so the event is retried', async () => {
  const sb = load();
  setup(sb, (opts) => opts.success({result: 'Error'}));
  const p = run(sb, 'loadFrames({7: events[7]})');
  await p;
  assert.strictEqual(sb.ajaxCalls.length, 1, 'first call should issue one request');
  assert.strictEqual(run(sb, 'events[7].FramesById'), undefined,
      'the loading marker was not removed after the error result');
  await run(sb, 'loadFrames({7: events[7]})');
  assert.strictEqual(sb.ajaxCalls.length, 2,
      'a second loadFrames did not issue a new request for the closed event');
});

test('success with frames fills FramesById and the closed event is not re-requested', async () => {
  const sb = load();
  setup(sb, (opts) => opts.success({frames: [
    {Frame: {EventId: 7, FrameId: 1, TimeStampSecs: '1500', Score: '10'}},
    {Frame: {EventId: 7, FrameId: 2, TimeStampSecs: '1501', Score: '20'}},
  ]}));
  const p = run(sb, 'loadFrames({7: events[7]})');
  await p;
  assert.strictEqual(sb.ajaxCalls.length, 1, 'first call should issue one request');
  const frames = run(sb, 'events[7].FramesById');
  assert.ok(frames, 'FramesById was not stored');
  assert.ok(frames[1] && frames[2], 'frames were not stored by FrameId');
  assert.strictEqual(run(sb, 'maxScore'), 20);
  await run(sb, 'loadFrames({7: events[7]})');
  assert.strictEqual(sb.ajaxCalls.length, 1,
      'a closed event whose frames loaded must not be re-requested');
});

test('the promise waits for every batch, not the first one to answer', async () => {
  // Over ~1000 characters of /EventId:N terms the ids go out in several
  // requests. The caller redraws the timeline once the promise settles, so
  // settling on the first reply left the other batches' alarm frames undrawn.
  const sb = load();
  const pending = [];
  setup(sb, (opts) => pending.push(opts));
  run(sb, `var many = {};
    for (let id = 10000; id < 10200; id++) {
      events[id] = {Id: id, MonitorId: 1, EndDateTime: "2020-01-01 00:00:00"};
      many[id] = events[id];
    }`);
  let settled = false;
  const p = run(sb, 'loadFrames(many)');
  p.then(() => {
    settled = true;
  });
  assert.ok(pending.length > 1, 'expected several batches, got ' + pending.length);
  const frameFor = (url) => {
    const id = parseInt(/EventId:(\d+)/.exec(url)[1]);
    return {frames: [{Frame: {EventId: id, FrameId: 1, TimeStampSecs: '1500', Score: '5'}}]};
  };
  const answer = (i) => pending[i].success(frameFor(sb.ajaxCalls[i].url));
  answer(0);
  await new Promise((r) => setImmediate(r));
  assert.strictEqual(settled, false, 'resolved after 1 of ' + pending.length + ' batches');
  for (let i = 1; i < pending.length; i++) answer(i);
  await p;
  assert.strictEqual(settled, true);
  const last = sb.ajaxCalls[pending.length - 1].url;
  const lastId = parseInt(/EventId:(\d+)/.exec(last)[1]);
  assert.ok(run(sb, 'events[' + lastId + '].FramesById[1]'), 'last batch frames missing');
});

(async function() {
  for (const {name, fn} of tests) {
    try {
      await fn();
      console.log('  ok ' + name);
      passed++;
    } catch (e) {
      console.error('  FAIL ' + name);
      console.error('    ' + (e.stack || e.message));
      failed++;
    }
  }
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  process.exit(failed ? 1 : 0);
})();
