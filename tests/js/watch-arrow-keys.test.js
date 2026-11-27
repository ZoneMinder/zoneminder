'use strict';

// The watch page switches monitors with the cycle buttons. The event view also
// switches events with the Left/Right arrow keys, so the watch page should do
// the same for monitors.

const assert = require('assert');
const {loadWatch, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup(overrides = {}) {
  const handlers = {};
  const doc = {
    getElementById: (id) => (id === 'use-old-zoom-pan' ? {} : null),
    referrer: '', querySelector: () => null, querySelectorAll: () => [],
    addEventListener() {}, createElement: () => ({}),
  };
  function makeEl() {
    const el = {
      length: 1,
      on(evt, a, b) {
        if (evt === 'keydown') (handlers.keydown = handlers.keydown || []).push(a);
        return el;
      },
      off: () => el, prop: () => el, attr: () => el,
      addClass: () => el, removeClass: () => el, hasClass: () => false,
      hide: () => el, show: () => el, html: () => el, text: () => el,
      val: () => '', is: () => false, click: () => el,
    };
    return el;
  }
  function $j(sel) {
    if (sel === '.modal') return {length: overrides.modalVisible ? 1 : 0, is: () => !!overrides.modalVisible};
    return makeEl();
  }
  const monitorData = overrides.monitorData || [{id: 1}, {id: 2}];
  const g = loadWatch({
    $j, document: doc, monitorData,
    ResizeObserver: function() {
      this.observe = () => {};
    },
  });
  g.getCookie = () => null;
  g.zmPanZoom = {init() {}};
  g.bindButton = () => {};
  g.changeObjectClass = g.applyMonitorControllable = g.streamPrepareStart = () => {};
  g.monitorId = 1;
  g.cycle = false;
  g.cycleStart = () => {};
  g.cyclePause = () => {};
  g.panZoomEnabled = true;
  g.initPage();
  return {g, handler: (handlers.keydown || [])[0]};
}

console.log('watch.js arrow keys switch monitors');

test('ArrowRight switches to the next monitor', () => {
  const {g, handler} = setup();
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}});
  assert.deepStrictEqual(calls, ['next']);
});

test('ArrowLeft switches to the previous monitor', () => {
  const {g, handler} = setup();
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowLeft', target: {tagName: 'BODY'}});
  assert.deepStrictEqual(calls, ['prev']);
});

test('an arrow key in an input does nothing', () => {
  const {g, handler} = setup();
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowRight', target: {tagName: 'INPUT'}});
  handler({key: 'ArrowLeft', target: {tagName: 'SELECT'}});
  handler({key: 'ArrowRight', target: {tagName: 'TEXTAREA'}});
  assert.deepStrictEqual(calls, []);
});

test('an arrow key with a modal open does nothing', () => {
  const {g, handler} = setup({modalVisible: true});
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}});
  handler({key: 'ArrowLeft', target: {tagName: 'BODY'}});
  assert.deepStrictEqual(calls, []);
});

test('an arrow key with a modifier held does nothing', () => {
  const {g, handler} = setup();
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}, shiftKey: true});
  handler({key: 'ArrowLeft', target: {tagName: 'BODY'}, altKey: true});
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}, ctrlKey: true});
  assert.deepStrictEqual(calls, []);
});

test('an arrow key with only one monitor does nothing', () => {
  const {g, handler} = setup({monitorData: [{id: 1}]});
  assert.ok(handler, 'no keydown handler was registered');
  const calls = [];
  g.cycleNext = () => calls.push('next');
  g.cyclePrev = () => calls.push('prev');
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}});
  handler({key: 'ArrowLeft', target: {tagName: 'BODY'}});
  assert.deepStrictEqual(calls, []);
});

done();
