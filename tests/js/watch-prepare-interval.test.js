'use strict';

// streamPrepareStart() runs on every monitor switch and starts a poller that
// waits for the new stream to appear. It only stopped once a liveStream element
// was taller than 20px, so a switch before that (or a stream that never shows)
// left the old poller running and added another one each time.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup(live) {
  let next = 1;
  const g = loadWatch({
    setInterval: (fn) => {
      live.set(next, fn); return next++;
    },
    clearInterval: (id) => live.delete(id),
  });
  g.enableAlmBtn = makeHandlerSet();
  g.forceAlmBtn = makeHandlerSet();
  g.analyseBtn = makeHandlerSet();
  g.settingsBtn = makeHandlerSet();
  g.canEdit = {Monitors: true};
  g.MonitorStream = function() {
    this.setButton = () => {};
    this.setup_onclick = this.setup_onmove = this.setup_onpause = () => {};
    this.setup_onplay = this.setup_onalarm = this.manageAvailablePlayers = () => {};
    this.setBottomElement = () => {};
  };
  g.getCookie = () => null;
  g.setChannelStream = g.streamCmdPlay = g.manageStreamQualityVisibility = () => {};
  g.bindButton = () => {};
  g.getSettingsModal = () => {};
  g.canView = {Control: true, Events: false};
  g.history = {length: 2};
  g.currentMonitor = {monitorType: 'Local'};
  return g;
}

console.log('watch.js stream prepare poller');

test('a second streamPrepareStart leaves one poller running', () => {
  const live = new Map();
  const g = setup(live);
  g.streamPrepareStart(g.currentMonitor);
  g.streamPrepareStart(g.currentMonitor);
  g.streamPrepareStart(g.currentMonitor);
  assert.strictEqual(live.size, 1);
});

test('the poller stops once the stream is shown', () => {
  const live = new Map();
  const g = setup(live);
  g.streamPrepareStart(g.currentMonitor);
  const classes = new Set(['hidden-shift']);
  g.document.querySelector = () => ({offsetHeight: 100});
  g.document.getElementById = () => ({classList: {remove: (c) => classes.delete(c)}});
  g.monitorsSetScale = () => {};
  for (const fn of [...live.values()]) fn();
  assert.strictEqual(live.size, 0);
  assert.ok(!classes.has('hidden-shift'));
});

done();
