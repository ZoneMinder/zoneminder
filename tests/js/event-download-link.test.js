'use strict';

// The event view's Download button (#downloadBtn, event.php) is an <a> whose
// href event.php renders for the event the page was opened on. The view moves
// to another event without a page load when zms advances on its own
// (getCmdResponse -> eventQuery) or on prev/next, and both go through
// getEventResponse(). Nothing used to touch the link, so Download saved the
// first event's video. The server now sends the new event's link as
// DownloadUrl (ajax/status.php, built by Event::getStreamSrc()), which keeps a
// multi-server setup's host and the auth relay out of the JS.
//
// Run as: node tests/js/event-download-link.test.js [path/to/event.js]
// The event.js path can also be set in EVENT_JS.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.argv[2] || process.env.EVENT_JS ||
  path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(srcPath, 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

// Load event.js for event 10 of monitor 1. The download link starts as
// event.php would have written it, and records what is done to it.
function load() {
  const link = {
    href: 'index.php?mode=mp4&eid=10&view=view_video',
    title: 'Download 10-video.mp4',
    visible: true,
    attrs: [],
    shown: 0,
    hidden: 0,
    attr(name, value) {
      if (value === undefined) return this[name];
      this.attrs.push([name, value]);
      this[name] = value;
      return undefined;
    },
    show() {
      this.visible = true;
      this.shown++;
    },
    hide() {
      this.visible = false;
      this.hidden++;
    },
  };
  const $j = function(selector) {
    if (selector === '#downloadBtn') return link;
    return makeChainable();
  };
  $j.getJSON = function() {
    const p = {done: () => p, fail: () => p, always: () => p};
    return p;
  };
  const globals = {
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: () => {},
    thisUrl: 'index.php',
    monitorUrl: 'index.php',
    canEdit: {Events: true},
    canView: {Events: true, Monitors: true},
    zmAuth: {hash: ''},
    history: {replaceState() {}},
    rates: [0, 100, 200],
    filterQuery: '',
    sortQuery: '',
    eventData: {
      Id: 10,
      MonitorId: '1',
      Width: 640,
      Height: 480,
      Length: '10',
      Frames: '100',
      DefaultVideo: '10-video.mp4',
      DownloadUrl: 'index.php?mode=mp4&eid=10&view=view_video',
    },
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  return {globals, link};
}

function eventResponse(g, next) {
  g.zmsEventChanged = () => {};
  g.getStat = () => {};
  g.checkStreamForErrors = () => false;
  g.getEventResponse({event: next});
}

test('moving to another event points the download link at it', () => {
  const {globals, link} = load();
  eventResponse(globals, {
    Id: 11,
    DefaultVideo: '11-video.mp4',
    DownloadUrl: 'index.php?mode=mp4&eid=11&view=view_video',
  });
  assert.strictEqual(link.href, 'index.php?mode=mp4&eid=11&view=view_video');
});

test('a link the server built for another host is used as it is', () => {
  const {globals, link} = load();
  eventResponse(globals, {
    Id: 11,
    DefaultVideo: '11-video.mp4',
    DownloadUrl: 'https://nvr2.example:8443/zm/index.php?mode=mp4&eid=11&view=view_video&auth=abc123&user=me',
  });
  assert.strictEqual(link.href,
      'https://nvr2.example:8443/zm/index.php?mode=mp4&eid=11&view=view_video&auth=abc123&user=me');
});

test('an event with no video hides the link, as event.php does', () => {
  const {globals, link} = load();
  eventResponse(globals, {Id: 11, DefaultVideo: '', DownloadUrl: ''});
  assert.ok(!link.visible, 'link left visible for an event with no video');
});

test('an event with a video shows a link that was hidden', () => {
  const {globals, link} = load();
  link.visible = false; // the page's event had no video
  eventResponse(globals, {
    Id: 11,
    DefaultVideo: '11-video.mp4',
    DownloadUrl: 'index.php?mode=mp4&eid=11&view=view_video',
  });
  assert.ok(link.visible, 'link stayed hidden for an event with a video');
});

test('the same event again leaves the link alone', () => {
  const {globals, link} = load();
  eventResponse(globals, {
    Id: 10,
    DefaultVideo: '10-video.mp4',
    DownloadUrl: 'index.php?mode=mp4&eid=10&view=view_video',
  });
  assert.deepStrictEqual(link.attrs, []);
  assert.strictEqual(link.shown + link.hidden, 0);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
