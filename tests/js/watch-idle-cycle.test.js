'use strict';

// The "are you still watching" modal is built once and its hidden handler was
// created with the cycle state of the first idle timeout. Later timeouts reused
// that stale value, so the cycle was resumed or left paused wrongly.

const assert = require('assert');
const {loadWatch, makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup() {
  const chain = makeChainable();
  const timeouts = [];
  const modal = {
    length: 1, handlers: {},
    on(evt, fn) {
      this.handlers[evt] = fn;
      return this;
    },
    modal() {},
  };
  let modalBuilt = false;
  const doc = {
    getElementById: (id) => (id === 'use-old-zoom-pan' ? {} : null),
    referrer: '', querySelector: () => null, querySelectorAll: () => [],
    addEventListener() {}, createElement: () => ({}),
  };
  const $j = Object.assign((sel) => {
    if (sel === '#AYSWModal') return modalBuilt ? modal : {length: 0};
    return chain;
  }, {
    getJSON: () => {
      const p = {done: (fn) => {
        fn({html: ''}); return p;
      }, fail: () => p};
      return p;
    },
  });
  const g = loadWatch({
    $j, document: doc, ZM_WEB_VIEWING_TIMEOUT: 5,
    setTimeout: (fn) => timeouts.push(fn),
    monitorData: [{id: 1}, {id: 2}],
    ResizeObserver: function() {
      this.observe = () => {};
    },
  });
  g.getCookie = () => null;
  g.zmAuth = {hash: ''};
  g.lightStatusReq = g.indicatorLightStatusReq = () => {};
  g.zmPanZoom = {init() {}};
  g.bindButton = () => {};
  g.changeObjectClass = g.applyMonitorControllable = g.streamPrepareStart = () => {};
  g.insertModalHtml = () => {
    modalBuilt = true;
    return modal;
  };
  g.streamCmdStop = g.streamCmdPlay = () => {};
  g.monitorId = 1;
  g.cycleStarts = 0;
  g.cycleStart = () => {
    g.cycleStarts++;
  };
  g.cyclePause = () => {
    g.cycle = false;
  };
  g.cycle = false;
  g.panZoomEnabled = true;
  return {g, timeouts, modal};
}

console.log('watch.js idle timeout and the cycle');

test('each idle timeout resumes the cycle only if it was running then', () => {
  const {g, timeouts, modal} = setup();
  g.initPage();

  // First idle timeout: not cycling.
  timeouts[timeouts.length - 1]();
  modal.handlers['hidden.bs.modal']();
  assert.strictEqual(g.cycleStarts, 0);

  // The user starts cycling, then goes idle again.
  g.cycle = true;
  timeouts[timeouts.length - 1]();
  modal.handlers['hidden.bs.modal']();
  assert.strictEqual(g.cycleStarts, 1, 'cycle was not resumed');
});

test('a timeout re-armed under the open modal keeps the original cycle state', () => {
  const {g, timeouts, modal} = setup();
  g.initPage();
  g.cycle = true;
  timeouts[timeouts.length - 1]();
  timeouts[timeouts.length - 1]();
  modal.handlers['hidden.bs.modal']();
  assert.strictEqual(g.cycleStarts, 1);
});

done();
