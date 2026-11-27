'use strict';

// The watch page rendered the monitor's zones nowhere, and unlike montage it had
// no way to show or hide them. The toggle has to keep the chosen state across
// page loads the same way montage does, with a cookie read back on load.

const assert = require('assert');
const {loadWatch, makeChainable, runner} = require('./watch-sandbox');
const {test, done} = runner();

function makeClassList() {
  const classes = new Set();
  return {
    contains: (name) => classes.has(name),
    add: (name) => classes.add(name),
    remove: (name) => classes.delete(name),
    toggle: (name, force) => {
      const want = force === undefined ? !classes.has(name) : !!force;
      if (want) classes.add(name);
      else classes.delete(name);
      return want;
    },
  };
}

function setup(savedZones) {
  const handlers = {};
  const cookies = {};
  if (savedZones !== undefined) cookies.zmWatchShowZones = savedZones;

  const monitor = {classList: makeClassList()};
  const icon = {textContent: ''};
  const button = {
    attrs: {},
    classList: makeClassList(),
    querySelector: (sel) => (sel === '.material-icons' ? icon : null),
    setAttribute(name, value) {
      this.attrs[name] = value;
    },
  };
  const doc = {
    getElementById: (id) => {
      if (id === 'monitor') return monitor;
      if (id === 'toggleZonesButton') return button;
      if (id === 'use-old-zoom-pan') return {checked: false};
      return null;
    },
    referrer: '', querySelector: () => null, querySelectorAll: () => [],
    addEventListener() {}, createElement: () => ({}),
  };

  const g = loadWatch({
    $j: makeChainable(),
    document: doc,
    monitorData: [{id: 1}],
    ResizeObserver: function() {
      this.observe = () => {};
    },
  });
  g.monitorId = 1;
  g.getCookie = (name) => (name in cookies ? cookies[name] : null);
  g.setCookie = (name, value) => {
    cookies[name] = value;
  };
  g.showZonesString = 'Show Zones';
  g.hideZonesString = 'Hide Zones';
  g.zmPanZoom = {init() {}};
  g.bindButton = (selector, action, data, fn) => {
    handlers[selector + ':' + action] = fn;
  };
  g.changeObjectClass = g.applyMonitorControllable = g.streamPrepareStart = () => {};
  g.cyclePause = () => {};
  g.panZoomEnabled = true;

  g.initPage();
  return {g, monitor, button, icon, cookies, handlers};
}

console.log('watch.js zone toggle');

test('clicking the zones button toggles the overlay and saves the choice', () => {
  const {monitor, button, icon, cookies, handlers} = setup('0');
  const click = handlers['#toggleZonesButton:click'];
  assert.ok(click, 'initPage did not bind the zones button');

  click();
  assert.strictEqual(monitor.classList.contains('hide-zones'), false);
  assert.strictEqual(cookies.zmWatchShowZones, '1');
  assert.strictEqual(button.attrs.title, 'Hide Zones');
  assert.strictEqual(icon.textContent, 'layers_clear');

  click();
  assert.strictEqual(monitor.classList.contains('hide-zones'), true);
  assert.strictEqual(cookies.zmWatchShowZones, '0');
  assert.strictEqual(button.attrs.title, 'Show Zones');
  assert.strictEqual(icon.textContent, 'layers');
});

test('the saved zones setting is applied on load', () => {
  const shown = setup('1');
  assert.strictEqual(shown.monitor.classList.contains('hide-zones'), false);

  const hidden = setup('0');
  assert.strictEqual(hidden.monitor.classList.contains('hide-zones'), true);
});

done();
