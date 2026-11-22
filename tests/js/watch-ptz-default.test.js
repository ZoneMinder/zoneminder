'use strict';

// watch.php shows the PTZ panel by default (no cookie) and honours ?ptzShow=,
// but applyMonitorControllable() decided from the cookie alone, so a first
// visit rendered the panel and then hid it.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup(ptzVisible, controllable) {
  const classes = new Set(['btn-secondary']);
  const toggle = {
    disabled: false,
    classList: {
      add: (c) => classes.add(c), remove: (c) => classes.delete(c),
    },
  };
  const g = loadWatch({
    document: {
      getElementById: (id) => (id === 'ptzToggle' ? toggle : null),
      querySelector: () => null, querySelectorAll: () => [],
      addEventListener() {}, createElement: () => ({}),
    },
  });
  const calls = [];
  g.sidebarControls = Object.assign(makeHandlerSet(), {
    show: () => calls.push('show'), hide: () => calls.push('hide'),
    html: () => calls.push('html'),
  });
  g.getCookie = () => null;
  g.changeObjectClass = () => {};
  g.lightStatusReq = g.indicatorLightStatusReq = () => {};
  g.ptzVisible = ptzVisible;
  g.currentMonitor = {monitorControllable: controllable, ptzControls: ''};
  return {g, calls, classes};
}

console.log('watch.js PTZ panel initial state');

test('with no cookie the panel follows the server side default', () => {
  const {g, calls, classes} = setup(true, true);
  g.applyMonitorControllable();
  assert.ok(calls.includes('show'));
  assert.ok(!calls.includes('hide'));
  assert.ok(classes.has('btn-primary'));
});

test('a hidden state stays hidden', () => {
  const {g, calls} = setup(false, true);
  g.applyMonitorControllable();
  assert.ok(calls.includes('hide'));
  assert.ok(!calls.includes('show'));
});

test('toggling keeps the state for the next monitor', () => {
  const {g, calls} = setup(true, true);
  g.sidebarControls.is = () => true;
  g.setCookie = () => {};
  g.monitorsSetScale = () => {};
  g.ptzToggle();
  assert.strictEqual(g.ptzVisible, false);
  calls.length = 0;
  g.applyMonitorControllable();
  assert.ok(calls.includes('hide'));
});

done();
