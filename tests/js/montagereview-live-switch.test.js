'use strict';

// Switching between history and live in montage review submits the header
// form. The Fit toggle and the scale slider only change JS state, so the
// submitted form carried the fit value the page was rendered with and no scale
// at all: pressing Fit then Live came back in the old fit mode, and a 0.5x
// scale was reset to 1x.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js');
const src = fs.readFileSync(file, 'utf8');

function makeForm() {
  const form = {
    elements: {fit: {value: '1'}, live: {value: '0'}},
    submitted: null,
    appendChild(el) {
      this.elements[el.name] = el;
    },
    submit() {
      this.submitted = {fit: this.elements.fit.value, scale: this.elements.scale && this.elements.scale.value};
    },
  };
  return form;
}

function run(fit, scale) {
  const form = makeForm();
  const proxy = new Proxy(function() {}, {
    get: (t, p) => (p === 'then' ? undefined : (p === 'length' ? 0 : proxy)),
    apply: () => proxy,
  });
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: (id) => (id === 'montagereview_form' ? form : {style: {}, addEventListener() {}}),
      createElement: () => ({style: {}}),
      body: {},
    },
    $j: Object.assign(function() {
      return proxy;
    }, {ajax: () => proxy}),
    HTMLCanvasElement: function() {}, Image: function() {},
    Math, Date, Object, Promise, parseInt, parseFloat, Array, Number, isNaN,
    setInterval: () => 1, clearInterval() {}, setTimeout: () => 1,
    setCookie() {}, secs2dbstr: String, secs2inputstr: String,
    zmAuth: {applyTo: (u) => u, appendTo: (u) => u},
    addOrUpdateRandParam: (u) => u, navbar_type: 'left',
  };
  sandbox.window = sandbox;
  sandbox.window.addEventListener = () => {};
  vm.createContext(sandbox);
  vm.runInContext(`
    var currentScale=${scale}, liveMode=0, fitMode=${fit}, currentSpeed=1, speedIndex=5, lastSpeedIndex=0;
    var currentDisplayInterval=100, timerInterval, timerObj, freeTimeLastIntervals=[];
    var imageLoadTimesEvaluated=0, imageLoadTimesNeeded=15, timeLabelsFractOfRow=0.9;
    const events = {}; const events_for_monitor = {};
    var maxScore=0, monitorName=[], monitorLoading=[], monitorServerId=[], monitorImageObject=[], monitorImageURL=[],
      monitorLoadingStageURL=[], monitorLoadStartTimems=[], monitorLoadEndTimems=[], monitorColour=[],
      monitorWidth=[], monitorHeight=[], monitorIndex=[], monitorNormalizeScale=[], monitorZoomScale=[],
      monitorCanvasObj=[], monitorCanvasCtx=[], monitorPtr=[];
    var numMonitors=0, minTimeSecs=1000, maxTimeSecs=2000, rangeTimeSecs=1001, currentTimeSecs=1000;
    var speeds=[0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];
    var cWidth=1000, cHeight=100, rowHeight=10, canvas, ctx=null, underSlider;
    var monitorData=[], Servers={}, serverId=0, Storage=[], thisUrl='', currentView='montagereview';
  `, sandbox);
  vm.runInContext(src, sandbox);
  vm.runInContext('setLive(1)', sandbox);
  return form.submitted;
}

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name + '\n    ' + e.message);
    failed++;
  }
}

test('Live switch submits the fit mode the user toggled to', () => {
  assert.strictEqual(run(0, 1).fit, '0');
});
test('Live switch submits the current scale', () => {
  assert.strictEqual(String(run(0, 0.5).scale), '0.5');
});
process.exit(failed ? 1 : 0);
