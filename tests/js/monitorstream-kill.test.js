'use strict';

// MonitorStream.kill() used to do an unconditional `stream.onerror = null` on
// whatever element the monitor was currently using.  For an <img> that clears the
// inherited event-handler accessor, which is what it was written for.  For the
// go2rtc <video-stream> element, onerror is a *method* on VideoRTC.prototype, so
// the assignment creates an own property that shadows the method for the life of
// the element.  replaceDOMElement() reuses a node whose tag already matches, so a
// kill()-then-start() cycle handed the same poisoned element back to
// select_go2rtc(), and the next websocket failure threw
// "TypeError: this.onerror is not a function" out of VideoRTC.onconnect().
// refs #5025

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

// MonitorStream.js is a browser file that leans on globals defined by skin.js and
// the view templates.  Rather than track that list, resolve any unknown global to
// undefined so the constructor can run; only the handful this test actually cares
// about are given real values.
// A callable, infinitely chainable stand-in, enough for the $j/jQuery calls the
// constructor makes.
function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

function loadMonitorStream() {
  const globals = {
    $j: makeChainable(),
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    currentView: 'watch',
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
    has: () => true, // make every bare identifier resolve against this object
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'MonitorStream.js'});
  assert.strictEqual(typeof globals.MonitorStream, 'function',
      'MonitorStream.js did not define MonitorStream');
  return globals;
}

// A stand-in for the go2rtc custom element: onerror lives on the prototype,
// exactly as `class VideoStream extends VideoRTC { onerror(ev) {...} }` puts it.
function makeVideoStreamElement() {
  class VideoRTC {
    onerror(ev) {
      this.errorsHandled++;
    }
    onclose() {}
  }
  const el = new VideoRTC();
  el.errorsHandled = 0;
  Object.defineProperty(el, 'nodeName', {value: 'VIDEO-STREAM'});
  return el;
}

function makeImgElement() {
  // An <img>'s onerror/onload are inherited accessors backed by an internal slot;
  // a plain writable data property is a close enough stand-in for "assignable".
  return {nodeName: 'IMG', onerror: () => {}, onload: () => {}};
}

function makeMonitor(sandbox, element) {
  const monitor = new sandbox.MonitorStream({
    id: 1, name: 'test', connKey: null, url: '', url_to_zms: '',
    width: 640, height: 480,
  });
  monitor.element = element;
  monitor.getElement = () => element;
  monitor.activePlayer = 'go2rtc';
  monitor.started = false;
  monitor.stop = () => {}; // kill() delegates the rest of the teardown to stop()
  return monitor;
}

console.log('MonitorStream.kill() and the go2rtc <video-stream> element');

test('the shadowing hazard is real: assigning null hides a prototype method', () => {
  const el = makeVideoStreamElement();
  assert.strictEqual(typeof el.onerror, 'function');
  el.onerror = null;
  assert.strictEqual(typeof el.onerror, 'object',
      'assignment should create an own property shadowing the prototype method');
  assert.throws(() => el.onerror({}), TypeError);
});

test('kill() leaves <video-stream> onerror callable', () => {
  const sandbox = loadMonitorStream();
  const el = makeVideoStreamElement();
  makeMonitor(sandbox, el).kill();
  assert.strictEqual(typeof el.onerror, 'function',
      'kill() clobbered VideoRTC.prototype.onerror');
  el.onerror({}); // what VideoRTC.onconnect()'s error listener does
});

test('kill() does not add an own onerror to <video-stream>', () => {
  const sandbox = loadMonitorStream();
  const el = makeVideoStreamElement();
  makeMonitor(sandbox, el).kill();
  assert.ok(!Object.prototype.hasOwnProperty.call(el, 'onerror'),
      'kill() left an own onerror property on the element');
  assert.ok(!Object.prototype.hasOwnProperty.call(el, 'onload'),
      'kill() left an own onload property on the element');
});

test('a killed <video-stream> survives being reused by a restart', () => {
  // replaceDOMElement() returns the same node when the tag already matches, so
  // the element kill() touched is the one the next connect() attaches to.
  const sandbox = loadMonitorStream();
  const el = makeVideoStreamElement();
  const monitor = makeMonitor(sandbox, el);
  monitor.kill();
  monitor.kill();
  // What VideoRTC.onconnect() registers on the websocket (video-rtc.js:308).
  const listener = (ev) => el.onerror(ev);
  listener({});
  assert.strictEqual(el.errorsHandled, 1,
      'the reused element no longer handles websocket errors after kill()');
});

test('kill() still clears the img handlers', () => {
  const sandbox = loadMonitorStream();
  const el = makeImgElement();
  const monitor = makeMonitor(sandbox, el);
  monitor.activePlayer = 'zms';
  monitor.kill();
  assert.strictEqual(el.onerror, null, 'img onerror should still be cleared');
  assert.strictEqual(el.onload, null, 'img onload should still be cleared');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
