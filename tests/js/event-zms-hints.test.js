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

// The commands from take(), without the batch number.
const cmds = (list) => list.map(({dir, eventid, srcid}) => ({dir, eventid, srcid}));
const both = (src, next, prev) => [
  {dir: 0, eventid: next, srcid: src},
  {dir: 1, eventid: prev, srcid: src},
];
// Send the hints for current and have both commands reach zms.
function deliver(hints, current) {
  const sent = hints.take(current);
  for (const cmd of sent) hints.response(cmd.batch, true);
  return sent;
}

console.log('take');
test('nothing to send before any neighbours arrive', () => {
  assert.deepStrictEqual(cmds(ZM.create().take(10)), []);
});
test('sends both directions for the event zms is playing', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 11, 9));
});
test('holds hints for an event zms is not playing', () => {
  // The page's first neighbours can arrive before zms answers, or after it
  // has moved on.
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  assert.deepStrictEqual(cmds(hints.take(11)), []);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 11, 9));
});
test('a filter boundary is sent as 0', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 0, 9));
});
test('sent once', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  const sent = hints.take(10);
  assert.deepStrictEqual(cmds(hints.take(10)), []);
  for (const cmd of sent) hints.response(cmd.batch, true);
  assert.deepStrictEqual(cmds(hints.take(10)), []);
});

console.log('response');
test('sent again when either command did not reach zms', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  const [a, b] = hints.take(10);
  hints.response(a.batch, true);
  hints.response(b.batch, false);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 11, 9));
});
test('a reply for older hints does not settle the new ones', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  const old = hints.take(10);
  hints.set(11, 12, 10, 0);
  for (const cmd of old) hints.response(cmd.batch, true);
  assert.deepStrictEqual(cmds(hints.take(11)), both(11, 12, 10));
});
test('the same neighbours again are not resent', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  deliver(hints, 10);
  hints.set(10, 11, 9, 1000);
  assert.deepStrictEqual(cmds(hints.take(10)), []);
});
test('changed neighbours for the same event are sent', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  deliver(hints, 10);
  hints.set(10, 14, 9, 6000);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 14, 9));
});

test('a reply to an earlier send of the same event does not settle a later one', () => {
  // A boundary refresh replaces the hints for event 10 while the first send is
  // still out; its replies must not mark the second send delivered.
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  const first = hints.take(10);
  hints.set(10, 14, 9, 6000);
  for (const cmd of first) hints.response(cmd.batch, true);
  const second = hints.take(10);
  hints.response(second[0].batch, false);
  hints.response(second[1].batch, true);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 14, 9));
});

test('a new send waits for the replies to the one before it', () => {
  // Separate requests can reach zms out of order; a boundary 0 arriving after
  // the event found since would leave zms with the 0.
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  const first = hints.take(10);
  hints.set(10, 14, 9, 6000);
  assert.deepStrictEqual(cmds(hints.take(10)), []);
  hints.response(first[0].batch, true);
  assert.deepStrictEqual(cmds(hints.take(10)), []);
  hints.response(first[1].batch, false);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 14, 9));
});

console.log('reset');
test('a restarted zms gets the hints again', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  deliver(hints, 10);
  hints.reset();
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 11, 9));
});

test('replies to sends to the old zms do not count', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  const old = hints.take(10);
  hints.reset();
  const fresh = hints.take(10);
  for (const cmd of old) hints.response(cmd.batch, true);
  hints.response(fresh[0].batch, false);
  hints.response(fresh[1].batch, true);
  assert.deepStrictEqual(cmds(hints.take(10)), both(10, 11, 9));
});

console.log('needsLookup');
const retry = ZM.LOOKUP_RETRY_MS;
const later = ZM.BOUNDARY_REFRESH_MS;
test('looks up an event with no neighbours yet', () => {
  assert.strictEqual(ZM.create().needsLookup(10, false, 0), true);
});
test('one lookup at a time', () => {
  const hints = ZM.create();
  hints.lookingUp(10, 0);
  assert.strictEqual(hints.needsLookup(10, false, ZM.LOOKUP_TIMEOUT_MS - 1), false);
});
test('a failed lookup is retried, not at once', () => {
  // Without neighbours zms waits at the end of the event and then stops.
  const hints = ZM.create();
  const lookup10 = hints.lookingUp(10, 0);
  hints.lookupDone(lookup10);
  assert.strictEqual(hints.needsLookup(10, false, retry - 1), false);
  assert.strictEqual(hints.needsLookup(10, false, retry), true);
});
test('a lookup with no reply is given up on', () => {
  const hints = ZM.create();
  hints.lookingUp(10, 0);
  assert.strictEqual(hints.needsLookup(10, false, ZM.LOOKUP_TIMEOUT_MS), true);
});
test('neighbours of the previous event do not count', () => {
  // The lookup after an event change failed: the hints still name event 10.
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  const lookup11 = hints.lookingUp(11, 0);
  hints.lookupDone(lookup11);
  assert.strictEqual(hints.needsLookup(11, false, retry), true);
});
test('a new event is looked up at once even while another lookup is out', () => {
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  hints.lookingUp(10, 0);
  assert.strictEqual(hints.needsLookup(11, false, 1), true);
});
test('neighbours in hand are not looked up again', () => {
  const hints = ZM.create();
  const lookup10 = hints.lookingUp(10, 0);
  hints.set(10, 11, 9, 1);
  hints.lookupDone(lookup10);
  assert.strictEqual(hints.needsLookup(10, false, 10 * later), false);
});
test('a boundary ahead is asked for again after a while', () => {
  // An event recorded after the page asked would otherwise never be played.
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.strictEqual(hints.needsLookup(10, false, later - 1), false);
  assert.strictEqual(hints.needsLookup(10, false, later), true);
});
test('only in the direction of play', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  assert.strictEqual(hints.needsLookup(10, true, later), false);
  hints.set(10, 11, 0, 0);
  assert.strictEqual(hints.needsLookup(10, true, later), true);
  assert.strictEqual(hints.needsLookup(10, false, later), false);
});
test('a boundary refresh waits a full interval before the next request', () => {
  const hints = ZM.create();
  hints.set(10, 0, 9, 0);
  const lookup10 = hints.lookingUp(10, later);
  hints.lookupDone(lookup10);
  assert.strictEqual(hints.needsLookup(10, false, later + 1), false);
  assert.strictEqual(hints.needsLookup(10, false, 2 * later), true);
});

test('neighbours are asked for again while zms is paused', () => {
  // zms stops at the end of an event whose next one was deleted, and resumes
  // when it is sent a different one.
  const hints = ZM.create();
  hints.set(10, 11, 9, 0);
  assert.strictEqual(hints.needsLookup(10, false, later, false), false);
  assert.strictEqual(hints.needsLookup(10, false, later - 1, true), false);
  assert.strictEqual(hints.needsLookup(10, false, later, true), true);
});

test('only the last lookup counts', () => {
  // A lookup given up on and retried can answer after the retry.
  const hints = ZM.create();
  const first = hints.lookingUp(10, 0);
  const retry = hints.lookingUp(10, ZM.LOOKUP_TIMEOUT_MS);
  assert.strictEqual(hints.isLatestLookup(first), false);
  assert.strictEqual(hints.isLatestLookup(retry), true);
  // The first's completion does not end the retry's wait.
  hints.lookupDone(first);
  assert.strictEqual(hints.needsLookup(10, false, ZM.LOOKUP_TIMEOUT_MS + 1), false);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
