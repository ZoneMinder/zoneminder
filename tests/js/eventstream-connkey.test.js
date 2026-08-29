'use strict';

// EventStream restarted itself in a loop: the status poll for a connkey whose
// zms had exited returned result=Error, recover() restarted the stream under a
// new connkey, and the reply already in flight for the old one arrived after
// that and restarted it again. 86 connkeys were created in 5 minutes while only
// 2 were ever polled, and each stream painted one frame before being replaced.
//
// Two rules break the loop: a reply for a connkey we no longer hold is not ours
// to act on, and only a zms that is really gone (reason=no_socket) justifies a
// restart at all.

const assert = require('assert');
const path = require('path');
const ES = require(path.join(__dirname, '../../web/js/EventStream.js'));
const EventStream = ES.EventStream;

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

// A stream sitting on connkey 000111, with recover() counted rather than run.
function makeStream() {
  const stream = new EventStream({
    monitorId: 24,
    monitorWidth: 1920,
    monitorHeight: 1080,
    url: 'index.php',
    url_to_zms: '/cgi-bin/nph-zms?monitor=24',
    canvas: null
  });
  stream.connKey = '000111';
  stream.streamCmdParms.connkey = '000111';
  stream.started = true;
  stream.recovered = 0;
  stream.recover = function() {
    this.recovered++;
  };
  return stream;
}

console.log('EventStream stale-reply handling');

test('a dead-zms error for the current connkey recovers', () => {
  const s = makeStream();
  s.getStreamCmdResponse(
      {result: 'Error', message: 'socket does not exist', reason: 'no_socket'},
      '000111');
  assert.strictEqual(s.recovered, 1);
});

test('the same error for a replaced connkey is ignored', () => {
  const s = makeStream();
  s.getStreamCmdResponse(
      {result: 'Error', message: 'socket does not exist', reason: 'no_socket'},
      '000222');
  assert.strictEqual(s.recovered, 0);
});

test('a stale Ok does not overwrite the live status', () => {
  const s = makeStream();
  s.status = {event: 2, progress: 5};
  s.getStreamCmdResponse({result: 'Ok', status: {event: 1, progress: 99}},
      '000222');
  assert.strictEqual(s.status.event, 2);
});

test('a timeout for the current connkey does not restart a live zms', () => {
  const s = makeStream();
  s.getStreamCmdResponse({result: 'Error', message: 'timed out', reason: 'timeout'},
      '000111');
  assert.strictEqual(s.recovered, 0);
});

test('a reply with no connkey is still acted on', () => {
  const s = makeStream();
  s.getStreamCmdResponse({result: 'Error', message: 'gone', reason: 'no_socket'});
  assert.strictEqual(s.recovered, 1);
});

console.log('\nEventStream.errorIsFatal');

test('no_socket is fatal, zms really is gone', () => {
  assert.strictEqual(EventStream.errorIsFatal('no_socket'), true);
});

test('timeout is not fatal, zms is most likely alive and busy', () => {
  assert.strictEqual(EventStream.errorIsFatal('timeout'), false);
});

test('transient is not fatal, the failure was local to php', () => {
  assert.strictEqual(EventStream.errorIsFatal('transient'), false);
});

test('a missing reason is fatal, preserving pre-reason behaviour', () => {
  assert.strictEqual(EventStream.errorIsFatal(undefined), true);
});

console.log('\nzms URL construction');

// UrlToZMS already ends in '?monitor=N'.
test('a second query separator is not appended', () => {
  const s = makeStream();
  let src = null;
  s.img = {set src(v) {
    src = v;
  }, get src() {
    return src;
  }};
  s.startDrawLoop = function() {};
  s.teardown = function() {};
  s.streamCmdTimer = 'held'; // any truthy value; start() overwrites it
  global.setInterval = function() {
    return 1;
  };
  s.start(1591128, {});
  assert.strictEqual(src.indexOf('?monitor=24&source=event'), src.indexOf('?'),
      'expected one ? in ' + src);
  assert.strictEqual(src.split('?').length - 1, 1);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
