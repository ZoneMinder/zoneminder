'use strict';

const assert = require('assert');
const path = require('path');

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

// Minimal stand-ins for the browser globals table-helpers.js touches. document
// has to exist before the module is required, because it registers the
// visibilitychange listener at load.
const listeners = {};
global.document = {
  visibilityState: 'visible',
  addEventListener: function(name, fn) {
    (listeners[name] = listeners[name] || []).push(fn);
  },
};
function fireVisibilityChange() {
  (listeners['visibilitychange'] || []).forEach((fn) => fn());
}

const ZM = require(path.join(__dirname, '../../web/js/table-helpers.js'));

// A stand-in for a bootstrap-table jQuery object, recording the calls made.
function fakeTable() {
  const calls = [];
  return {
    calls: calls,
    bootstrapTable: function(action) {
      calls.push(action);
    },
  };
}
function reset() {
  ZM.tablesPendingVisibility.length = 0;
  global.document.visibilityState = 'visible';
}

console.log('deferTableRequestWhileHidden');
test('does not defer while the page is visible', () => {
  reset();
  const t = fakeTable();
  assert.strictEqual(ZM.deferTableRequestWhileHidden(t), false);
  assert.deepStrictEqual(t.calls, [], 'must not touch the table when visible');
  assert.strictEqual(ZM.tablesPendingVisibility.length, 0);
});
test('defers and hides the spinner while hidden', () => {
  reset();
  global.document.visibilityState = 'hidden';
  const t = fakeTable();
  assert.strictEqual(ZM.deferTableRequestWhileHidden(t), true, 'caller must skip its request');
  assert.deepStrictEqual(t.calls, ['hideLoading']);
  assert.strictEqual(ZM.tablesPendingVisibility.length, 1);
});
test('records a repeatedly deferred table only once', () => {
  reset();
  global.document.visibilityState = 'hidden';
  const t = fakeTable();
  ZM.deferTableRequestWhileHidden(t);
  ZM.deferTableRequestWhileHidden(t);
  ZM.deferTableRequestWhileHidden(t);
  assert.strictEqual(ZM.tablesPendingVisibility.length, 1, 'auto-refresh ticks must not stack');
});

console.log('refreshTablesPendingVisibility');
test('refreshes a deferred table when the page becomes visible', () => {
  // The regression: bootstrap-table renders "No matching records found" over the
  // result of a request that was skipped, and without this nothing ever re-issues
  // it, so the table stays empty until a manual refresh (issue #5026).
  reset();
  global.document.visibilityState = 'hidden';
  const t = fakeTable();
  ZM.deferTableRequestWhileHidden(t);
  global.document.visibilityState = 'visible';
  fireVisibilityChange();
  assert.deepStrictEqual(t.calls, ['hideLoading', 'refresh']);
  assert.strictEqual(ZM.tablesPendingVisibility.length, 0, 'queue must drain');
});
test('does nothing while still hidden', () => {
  reset();
  global.document.visibilityState = 'hidden';
  const t = fakeTable();
  ZM.deferTableRequestWhileHidden(t);
  fireVisibilityChange();
  assert.deepStrictEqual(t.calls, ['hideLoading'], 'still hidden, nothing to refresh yet');
  assert.strictEqual(ZM.tablesPendingVisibility.length, 1, 'must stay queued');
});
test('refreshes every deferred table', () => {
  reset();
  global.document.visibilityState = 'hidden';
  const a = fakeTable();
  const b = fakeTable();
  ZM.deferTableRequestWhileHidden(a);
  ZM.deferTableRequestWhileHidden(b);
  global.document.visibilityState = 'visible';
  fireVisibilityChange();
  assert.deepStrictEqual(a.calls, ['hideLoading', 'refresh']);
  assert.deepStrictEqual(b.calls, ['hideLoading', 'refresh']);
});
test('a table re-deferred during refresh stays queued rather than looping', () => {
  // refresh() calls the ajax function synchronously, which defers again if the
  // page went back to hidden. Draining first keeps that from spinning.
  reset();
  global.document.visibilityState = 'hidden';
  const t = fakeTable();
  t.bootstrapTable = function(action) {
    t.calls.push(action);
    if (action === 'refresh') {
      global.document.visibilityState = 'hidden';
      ZM.deferTableRequestWhileHidden(t);
    }
  };
  ZM.deferTableRequestWhileHidden(t);
  global.document.visibilityState = 'visible';
  fireVisibilityChange();
  assert.deepStrictEqual(t.calls, ['hideLoading', 'refresh', 'hideLoading']);
  assert.strictEqual(ZM.tablesPendingVisibility.length, 1, 'queued for the next time it is shown');
});
test('is a no-op when nothing was deferred', () => {
  reset();
  fireVisibilityChange();
  assert.strictEqual(ZM.tablesPendingVisibility.length, 0);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
