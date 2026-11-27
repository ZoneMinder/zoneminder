'use strict';

// The rate picked in the Rate select was only sent to the zms already running.
// A monitor switched to afterwards was built from the page-load rate rendered
// into its stream, so it ignored the select.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js');
const src = fs.readFileSync(file, 'utf8');

function load() {
  const made = [];
  const sent = [];
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'then' ? undefined : (p === 'val' ? () => '10' : chain)),
    apply: () => chain,
  });
  function MonitorStream(m) {
    this.url_to_zms = m.url_to_zms;
    this.activePlayer = 'zms';
    this.status = {};
    this.manageAvailablePlayers = () => {};
    this.setBottomElement = () => {};
    this.streamCommand = (c) => sent.push(c);
    ['setup_onclick', 'setup_onmove', 'setup_onpause', 'setup_onplay',
      'setup_onalarm', 'setButton'].forEach((n) => this[n] = () => {});
    made.push(this);
  }
  const globals = {
    $j: (x) => ((x && x.value !== undefined) ? {val: () => x.value} : chain), $: chain, MonitorStream, made, sent,
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => ({classList: {add() {}, remove() {}}, options: []}), querySelector: () => null,
      querySelectorAll: () => [], addEventListener() {}, createElement: () => ({}),
    },
    getCookie: () => null, setCookie() {}, applyChosen() {}, manageChannelStream() {},
    canEdit: {Monitors: true}, panZoomEnabled: true, streamMode: 'jpeg',
    CMD_MAXFPS: 9, monitorData: [{id: 1, url_to_zms: 'z?monitor=1&mode=jpeg'}],
    ZM_WEB_VIEWING_TIMEOUT: 0, addEventListener() {},
    Object, Array, JSON, Set, parseInt, String, Number,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : t[p]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  return globals;
}

const g = load();
g.currentMonitor = {monitorStreamReplayBuffer: 0};
g.monIdx = 0;
const btn = {off: () => btn, on: () => btn, prop: () => '', hasClass: () => false};
g.enableAlmBtn = g.forceAlmBtn = g.analyseBtn = btn;
g.streamCmdPlay = () => {};
g.monitorStream = {streamCommand() {}};
g.changeRate({value: '1'});
g.streamStart({id: 2, url_to_zms: 'z?monitor=2&mode=jpeg'});
const url = g.made[g.made.length - 1].url_to_zms;
try {
  assert.ok(/[?&]maxfps=1(&|$)/.test(url), 'switched stream lost the rate: ' + url);
  g.changeRate({value: '5'});
  g.streamStart({id: 3, url_to_zms: 'z?monitor=3&maxfps=1&mode=jpeg'});
  const u2 = g.made[g.made.length - 1].url_to_zms;
  assert.strictEqual((u2.match(/maxfps=/g) || []).length, 1, u2);
  assert.ok(/maxfps=5(&|$)/.test(u2), u2);
  console.log('  ok rate follows a monitor switch\n\n1 passed, 0 failed');
} catch (e) {
  console.error('  FAIL ' + e.message + '\n\n0 passed, 1 failed');
  process.exit(1);
}
