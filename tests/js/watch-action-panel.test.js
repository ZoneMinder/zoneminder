'use strict';

// A monitor with manual actions but no control of its own gets its action
// buttons rendered into ptzControls, but applyMonitorControllable() only filled
// the panel for controllable monitors, so the buttons were never shown.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup(monitor) {
  const toggle = {
    disabled: false,
    classList: {add() {}, remove() {}},
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
    html: (h) => calls.push('html:' + h),
  });
  g.changeObjectClass = () => {};
  g.lightStatusReq = g.indicatorLightStatusReq = () => {};
  g.ptzVisible = true;
  g.currentMonitor = monitor;
  return {g, calls, toggle};
}

console.log('watch.js action only panel');

test('a monitor with action buttons but no control shows the panel', () => {
  const {g, calls, toggle} = setup(
      {monitorControllable: false, ptzControls: '<div>actions</div>'});
  g.applyMonitorControllable();
  assert.ok(calls.includes('html:<div>actions</div>'));
  assert.strictEqual(toggle.disabled, false);
});

test('a monitor with nothing to show still clears the panel', () => {
  const {g, calls, toggle} = setup(
      {monitorControllable: false, ptzControls: ''});
  g.applyMonitorControllable();
  assert.ok(calls.includes('html:'));
  assert.strictEqual(toggle.disabled, true);
});

done();
