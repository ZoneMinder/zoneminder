'use strict';

// A montage tile whose zms had exited restarted itself twice. The status poll
// runs every second, but a no_socket reply takes over a second to come back
// (stream.php waits for the socket to appear), and every request on the page
// goes through one shared ajaxQueue. So a second poll for the dead connkey was
// already queued when the first reply restarted the stream. When that second
// reply arrived it restarted the stream again, abandoning the zms the first
// restart had just started. A later poll addressed to that orphan found it
// shutting down and waited out the full 5 second timeout, holding up every
// other monitor's poll behind it.
//
// The rule, as in EventStream: a reply for a connkey we no longer hold is not
// ours to act on.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/js/MonitorStream.js'), 'utf8');

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

// Load MonitorStream.js with a jQuery.ajaxQueue that records each request and
// lets the test deliver its reply later, in whatever order it likes.
function loadMonitorStream() {
  const requests = [];
  const globals = {
    Object,
    Math,
    $j: makeChainable(),
    $: makeChainable(),
    jQuery: {
      ajaxQueue: (opts) => {
        const req = {data: opts.data, done: null};
        requests.push(req);
        const promise = {
          done: (fn) => {
            req.done = fn;
            return promise;
          },
          fail: () => promise,
        };
        return promise;
      },
    },
    zmAuth: {
      appendTo: (url) => url,
      applyTo: (url, connkey) => url + '&connkey=' + connkey,
    },
    refreshStreamSrc: (stream, newSrc) => {
      stream.src = newSrc;
    },
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'montage',
    CMD_QUERY: 99,
    CMD_QUIT: 17,
    getCookie: () => 'true',
    setCookie: () => {},
    document: {
      getElementById: () => null,
      querySelector: () => null,
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  return {sandbox: globals, requests};
}

// A zms-backed tile on connkey 000111, with quitConnKey() counted rather than
// sent so the test can tell how many times the stream was reloaded.
function makeMonitor() {
  const {sandbox, requests} = loadMonitorStream();
  const monitor = new sandbox.MonitorStream({
    id: 12, name: 'test', connKey: '000111', url: 'index.php',
    url_to_zms: '/cgi-bin/nph-zms?monitor=12', width: 640, height: 480,
  });
  const img = {nodeName: 'IMG', src: '/cgi-bin/nph-zms?monitor=12&connkey=000111'};
  monitor.getElement = () => img;
  monitor.activePlayer = 'zms';
  monitor.started = true;
  monitor.quits = [];
  monitor.quitConnKey = function(connkey) {
    this.quits.push(connkey);
  };
  return {monitor, requests, img};
}

const NO_SOCKET = {result: 'Error', message: 'socket does not exist', reason: 'no_socket'};

console.log('MonitorStream stale-reply handling');

test('a dead-zms reply for the current connkey reloads the stream', () => {
  const {monitor, requests, img} = makeMonitor();
  monitor.streamCmdQuery();
  requests[0].done(NO_SOCKET);
  assert.deepStrictEqual(monitor.quits, ['000111']);
  assert.notStrictEqual(monitor.connKey, '000111');
  assert.ok(img.src.endsWith('&connkey=' + monitor.connKey));
});

test('two polls queued for a dead zms reload the stream only once', () => {
  const {monitor, requests, img} = makeMonitor();
  // Both polls are queued before the first reply comes back.
  monitor.streamCmdQuery();
  monitor.streamCmdQuery();
  assert.strictEqual(requests[1].data.connkey, '000111');

  requests[0].done(NO_SOCKET);
  const replacement = monitor.connKey;
  requests[1].done(NO_SOCKET);

  assert.deepStrictEqual(monitor.quits, ['000111'],
      'the stale reply quit the zms that had just replaced the dead one');
  assert.strictEqual(monitor.connKey, replacement,
      'the stale reply replaced the connkey a second time');
  assert.ok(img.src.endsWith('&connkey=' + replacement));
});

test('a stale Ok does not overwrite the live status', () => {
  const {monitor, requests} = makeMonitor();
  monitor.streamCmdQuery();
  monitor.connKey = monitor.streamCmdParms.connkey = '000222';
  monitor.status = {state: 2};
  requests[0].done({result: 'Ok', status: {state: 0}});
  assert.strictEqual(monitor.status.state, 2);
});

test('the poll after a reload goes to the new connkey', () => {
  const {monitor, requests} = makeMonitor();
  monitor.streamCmdQuery();
  requests[0].done(NO_SOCKET);
  monitor.streamCmdQuery();
  assert.strictEqual(requests[requests.length - 1].data.connkey, monitor.connKey);
});

test('an alarm reply for the current connkey is still acted on', () => {
  const {monitor, requests} = makeMonitor();
  monitor.alarmCommand(1);
  requests[0].done(NO_SOCKET);
  assert.deepStrictEqual(monitor.quits, ['000111']);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
