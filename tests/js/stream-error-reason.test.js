'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname, '../../web/js/MonitorStream.js'));

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

console.log('streamErrorIsFatal');

// Only a missing zms should tear the stream down. Restarting replaces the
// connkey, which makes any zms still running unreachable, so treating a
// recoverable failure as fatal is what orphaned the process.
test('no_socket -> fatal, zms really is gone', () => {
  assert.strictEqual(ZM.streamErrorIsFatal('no_socket'), true);
});

test('timeout -> not fatal, zms is most likely alive and busy', () => {
  assert.strictEqual(ZM.streamErrorIsFatal('timeout'), false);
});

test('transient -> not fatal, the failure was local to php', () => {
  assert.strictEqual(ZM.streamErrorIsFatal('transient'), false);
});

test('invalid -> not fatal, restarting cannot fix a bad request', () => {
  assert.strictEqual(ZM.streamErrorIsFatal('invalid'), false);
});

// A php that predates the reason field sends no reason at all. Falling back to
// the old always-restart behaviour keeps a mixed-version install working.
test('missing reason -> fatal, preserves pre-reason behaviour', () => {
  assert.strictEqual(ZM.streamErrorIsFatal(undefined), true);
});

test('null reason -> fatal', () => {
  assert.strictEqual(ZM.streamErrorIsFatal(null), true);
});

test('empty reason -> fatal', () => {
  assert.strictEqual(ZM.streamErrorIsFatal(''), true);
});

// An unknown reason from a newer php should not be silently treated as fatal:
// the conservative choice is to leave the stream alone and retry.
test('unrecognised reason -> not fatal', () => {
  assert.strictEqual(ZM.streamErrorIsFatal('something_new'), false);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
