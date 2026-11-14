'use strict';

// The camera light state was queried only at page load, and only if the first
// monitor had a light button. Switching to a monitor that has one never asked
// the camera, so the button showed no state.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js light toggle');

test('applying a controllable monitor queries the light state of its buttons', () => {
  const requests = [];
  let lightButtons = 0;
  const toggle = {disabled: false, classList: {add() {}, remove() {}}};
  const g = loadWatch({
    document: {
      getElementById: (id) => (id === 'ptzToggle' ? toggle : null),
      querySelector: () => null, querySelectorAll: () => [],
      addEventListener() {}, createElement: () => ({}),
    },
  });
  g.sidebarControls = Object.assign(makeHandlerSet(), {
    html: (h) => {
      if (h) lightButtons = 1;
    },
  });
  g.$j = Object.assign((sel) => ({
    length: (sel === '.lightToggleBtn') ? lightButtons : 0,
  }), {
    getJSON: (url, data) => {
      requests.push(data && data.control);
      const p = {done: () => p, fail: () => p};
      return p;
    },
  });
  g.zmAuth = {hash: ''};
  g.monitorUrl = 'index.php';
  g.monitorId = 2;
  g.ptzVisible = true;
  g.changeObjectClass = () => {};
  g.currentMonitor = {monitorControllable: true, ptzControls: '<button class="lightToggleBtn">'};
  g.applyMonitorControllable();
  assert.deepStrictEqual(requests, ['lightStatus']);
});

done();
