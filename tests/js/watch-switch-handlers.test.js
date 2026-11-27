'use strict';

// streamStart() runs for every monitor switch, but the alarm buttons live in
// the page header and are not replaced, so each switch added another click
// handler and one click sent the command once per switch.

const assert = require('assert');
const {loadWatch, makeHandlerSet, runner} = require('./watch-sandbox');
const {test, done} = runner();

function setup() {
  const g = loadWatch();
  g.enableAlmBtn = makeHandlerSet();
  g.forceAlmBtn = makeHandlerSet();
  g.analyseBtn = makeHandlerSet();
  g.canEdit = {Monitors: true};
  g.MonitorStream = function() {
    this.setButton = () => {};
    this.setup_onclick = this.setup_onmove = this.setup_onpause = () => {};
    this.setup_onplay = this.setup_onalarm = this.manageAvailablePlayers = () => {};
    this.setBottomElement = () => {};
  };
  g.getCookie = () => null;
  g.setChannelStream = g.streamCmdPlay = g.manageStreamQualityVisibility = () => {};
  g.panZoomEnabled = true;
  g.monitorData = [{id: 1}, {id: 2}];
  g.monIdx = 0;
  return g;
}

console.log('watch.js handlers across monitor switches');

test('alarm buttons keep a single click handler after repeated streamStart', () => {
  const g = setup();
  g.streamStart();
  g.streamStart();
  g.streamStart();
  assert.strictEqual(g.enableAlmBtn.handlers.click.length, 1);
  assert.strictEqual(g.forceAlmBtn.handlers.click.length, 1);
});

test('settings button keeps a single click handler after repeated streamPrepareStart', () => {
  const g = setup();
  g.settingsBtn = makeHandlerSet();
  const editBtn = makeHandlerSet();
  g.$j = (sel) => (sel === '#editBtn' ? editBtn : g.settingsBtn);
  g.bindButton = (sel, action, data, fn) => g.$j(sel).on(action, fn);
  g.getSettingsModal = () => {};
  g.canView = {Control: true, Events: false};
  g.history = {length: 2};
  g.currentMonitor = {monitorType: 'Local'};
  g.streamPrepareStart(g.currentMonitor);
  g.streamPrepareStart(g.currentMonitor);
  assert.strictEqual(g.settingsBtn.handlers.click.length, 1);
  assert.strictEqual(editBtn.handlers.click.length, 1);
});

done();
