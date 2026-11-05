'use strict';

// The event view tells zms which events come next/prev in the filter being
// viewed, so gapless playback over zms follows it across monitors (refs #4186).
// event-zms-hints.js decides when those hints go out.

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname,
    '../../web/skins/classic/views/js/event-zms-hints.js'));

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

const both = (src, next, prev) => [
  {dir: 0, eventid: next, srcid: src},
  {dir: 1, eventid: prev, srcid: src},
];

console.log('take');
test('nothing to send before any neighbours arrive', () => {
  assert.deepStrictEqual(ZM.create().take(10), []);
});
test('sends both directions for the event zms is playing', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  assert.deepStrictEqual(hints.take(10), both(10, 11, 9));
});
test('holds hints for an event zms is not playing', () => {
  // The page's first neighbours can arrive before zms answers, or after it
  // has moved on.
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  assert.deepStrictEqual(hints.take(11), []);
  assert.deepStrictEqual(hints.take(10), both(10, 11, 9));
});
test('a filter boundary is sent as 0', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.deepStrictEqual(hints.take(10), both(10, 0, 9));
});
test('sent once', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.take(10);
  assert.deepStrictEqual(hints.take(10), []);
  hints.response(10, true);
  hints.response(10, true);
  assert.deepStrictEqual(hints.take(10), []);
});

console.log('response');
test('sent again when either command did not reach zms', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.take(10);
  hints.response(10, true);
  hints.response(10, false);
  assert.deepStrictEqual(hints.take(10), both(10, 11, 9));
});
test('a reply for older hints does not settle the new ones', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.take(10);
  hints.set(11, 12, 10, 0);
  hints.response(10, true);
  hints.response(10, true);
  assert.deepStrictEqual(hints.take(11), both(11, 12, 10));
});
test('the same neighbours again are not resent', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.take(10);
  hints.response(10, true);
  hints.response(10, true);
  hints.set(10, 11, 9, 1000);
  assert.deepStrictEqual(hints.take(10), []);
});
test('changed neighbours for the same event are sent', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  hints.take(10);
  hints.response(10, true);
  hints.response(10, true);
  hints.set(10, 14, 9, 6000);
  assert.deepStrictEqual(hints.take(10), both(10, 14, 9));
});

console.log('reset');
test('a restarted zms gets the hints again', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.take(10);
  hints.response(10, true);
  hints.response(10, true);
  hints.reset();
  assert.deepStrictEqual(hints.take(10), both(10, 11, 9));
});

console.log('needsRefresh');
const later = ZM.BOUNDARY_REFRESH_MS;
test('a boundary ahead is asked for again after a while', () => {
  // An event recorded after the page asked would otherwise never be played.
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.strictEqual(hints.needsRefresh(10, false, later - 1), false);
  assert.strictEqual(hints.needsRefresh(10, false, later), true);
});
test('only in the direction of play', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.strictEqual(hints.needsRefresh(10, true, later), false);
  hints.set(10, 11, 0, 0);
  assert.strictEqual(hints.needsRefresh(10, true, later), true);
  assert.strictEqual(hints.needsRefresh(10, false, later), false);
});
test('not for another event', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.strictEqual(hints.needsRefresh(11, false, later), false);
});
test('refreshing() waits a full interval before the next request', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  hints.refreshing(later);
  assert.strictEqual(hints.needsRefresh(10, false, later + 1), false);
  assert.strictEqual(hints.needsRefresh(10, false, 2 * later), true);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
