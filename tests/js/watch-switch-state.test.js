'use strict';

// State that has to follow a monitor switch or a stop/play cycle on the watch
// page:
//  - the WebSite refresh timer looked up #imageFeed, which does not exist (the
//    element id carries the monitor id), and kept running after a switch.
//  - the event list handlers were attached on every switch and the
//    bootstrap-table destroy does not remove them.
//  - streamCmdStop() set onplay to false and nothing put it back.
//  - cycleNext()/cyclePrev() kept the old countdown for the next monitor.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js WebSite refresh');

test('reloadWebSite reloads the current monitor feed element', () => {
  const feed = {innerHTML: '<iframe></iframe>'};
  const g = loadWatch({
    document: {
      getElementById: (id) => (id === 'imageFeed7' ? feed : null),
      querySelector: () => null, querySelectorAll: () => [],
      addEventListener() {}, createElement: () => ({}),
    },
  });
  g.monitorId = 7;
  g.reloadWebSite();
});

test('a monitor switch clears the previous WebSite refresh timer', () => {
  const live = new Map();
  let next = 1;
  const g = loadWatch({
    setInterval: (fn) => {
      live.set(next, fn); return next++;
    },
    clearInterval: (id) => live.delete(id),
  });
  g.settingsBtn = makeHandlerSet();
  g.bindButton = () => {};
  g.canView = {Control: false, Events: false};
  g.history = {length: 2};
  g.currentMonitor = {monitorType: 'WebSite', monitorRefresh: 5};
  g.monitorData = [{id: 1}];
  g.streamPrepareStart(g.currentMonitor);
  g.currentMonitor = {monitorType: 'WebSite', monitorRefresh: 5};
  g.streamPrepareStart(g.currentMonitor);
  g.currentMonitor = {monitorType: 'Ffmpeg', monitorRefresh: 0};
  g.streamStart = () => {};
  g.streamPrepareStart(g.currentMonitor);
  const refreshTimers = [...live.values()].filter((fn) => fn === g.reloadWebSite);
  assert.strictEqual(refreshTimers.length, 0);
});

console.log('watch.js event list handlers');

test('event list handlers are attached once across monitor switches', () => {
  const g = loadWatch();
  const table = makeHandlerSet();
  table.bootstrapTable = () => table;
  table.off = (evts) => {
    evts.split(' ').forEach((e) => delete table.handlers[e]);
    return table;
  };
  g.eventListTable = table;
  g.settingsBtn = makeHandlerSet();
  g.bindButton = () => {};
  g.canView = {Control: false, Events: true};
  g.history = {length: 2};
  g.streamStart = () => {};
  g.icons = {};
  g.currentMonitor = {monitorType: 'Ffmpeg'};
  g.streamPrepareStart(g.currentMonitor);
  g.streamPrepareStart(g.currentMonitor);
  g.streamPrepareStart(g.currentMonitor);
  assert.strictEqual(table.handlers['click-cell.bs.table'].length, 1);
  assert.strictEqual(table.handlers['post-body.bs.table'].length, 2);
  assert.strictEqual(table.handlers['all.bs.table'].length, 1);
});

console.log('watch.js stop then play');

test('play after stop restores the stream onplay callback', () => {
  const g = loadWatch();
  g.currentMonitor = {monitorStreamReplayBuffer: 0};
  g.setButtonState = g.setButtonStateWatch = () => {};
  g.monitorStream = {
    status: {}, stop() {}, started: false, start() {}, play() {},
    onplay: null,
  };
  g.monitorStream.onplay = g.onPlay;
  g.streamCmdStop();
  assert.ok(!g.monitorStream.onplay);
  g.streamCmdPlay(true);
  assert.strictEqual(g.monitorStream.onplay, g.onPlay);
});

console.log('watch.js cycle countdown');

test('stepping to the next monitor restarts the countdown', () => {
  const g = loadWatch();
  g.monitorData = [{id: 1}, {id: 2}];
  g.monIdx = 0;
  g.cycle = true;
  g.streamReStart = () => {};
  g.secondsToCycle = 4;
  g.cycleNext();
  assert.strictEqual(Number(g.secondsToCycle), 10);
});

test('stepping to the previous monitor restarts the countdown', () => {
  const g = loadWatch();
  g.monitorData = [{id: 1}, {id: 2}];
  g.monIdx = 1;
  g.cycle = true;
  g.streamReStart = () => {};
  g.secondsToCycle = 4;
  g.cyclePrev();
  assert.strictEqual(Number(g.secondsToCycle), 10);
});

done();
