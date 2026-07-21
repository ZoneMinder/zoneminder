'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname, '../../web/js/auth-helpers.js'));

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

console.log('authFailureAction');
test('401 Unauthorized -> login', () => {
  assert.strictEqual(ZM.authFailureAction(401), 'login');
});
test('403 Forbidden (stale auth hash, what zms returns) -> login', () => {
  assert.strictEqual(ZM.authFailureAction(403), 'login');
});
test('0 network error -> retry', () => {
  assert.strictEqual(ZM.authFailureAction(0), 'retry');
});
test('408 timeout -> retry', () => {
  assert.strictEqual(ZM.authFailureAction(408), 'retry');
});
test('502 bad gateway -> retry', () => {
  assert.strictEqual(ZM.authFailureAction(502), 'retry');
});
test('200 success -> ignore', () => {
  assert.strictEqual(ZM.authFailureAction(200), 'ignore');
});
test('404 not found -> ignore', () => {
  assert.strictEqual(ZM.authFailureAction(404), 'ignore');
});

console.log('loginRedirectUrl');
test('builds login url preserving console view', () => {
  assert.strictEqual(
      ZM.loginRedirectUrl('/zm/index.php', 'console'),
      '/zm/index.php?view=login&postLoginQuery=view%3Dconsole');
});
test('preserves montage view', () => {
  assert.strictEqual(
      ZM.loginRedirectUrl('/zm/index.php', 'montage'),
      '/zm/index.php?view=login&postLoginQuery=view%3Dmontage');
});
test('defaults to console when view missing', () => {
  assert.strictEqual(
      ZM.loginRedirectUrl('/zm/index.php', ''),
      '/zm/index.php?view=login&postLoginQuery=view%3Dconsole');
});

console.log('rebuildStreamSrc');
test('replaces auth hash in place', () => {
  assert.strictEqual(
      ZM.rebuildStreamSrc('/zm/cgi-bin/nph-zms?monitor=35&auth=OLD123&connkey=816890&mode=jpeg', 'NEW456', 816890),
      '/zm/cgi-bin/nph-zms?monitor=35&auth=NEW456&connkey=816890&mode=jpeg');
});
test('replaces connkey when a fresh one is supplied', () => {
  assert.strictEqual(
      ZM.rebuildStreamSrc('/zm/cgi-bin/nph-zms?monitor=35&auth=OLD123&connkey=816890&mode=jpeg', 'NEW456', 999999),
      '/zm/cgi-bin/nph-zms?monitor=35&auth=NEW456&connkey=999999&mode=jpeg');
});
test('swaps both auth and connkey (the reconnect case)', () => {
  // Regression: a broken montage <img> must reconnect with BOTH a fresh hash
  // and a fresh connkey, never the stale baked pair that storms zms.
  const broken = 'cgi-bin/nph-zms?monitor=35&auth=5c464e95&user=plaza&connkey=816890&scale=25&mode=jpeg';
  const out = ZM.rebuildStreamSrc(broken, 'fresh99', 123456);
  assert.strictEqual(out.indexOf('auth=5c464e95'), -1, 'stale auth must be gone');
  assert.strictEqual(out.indexOf('connkey=816890'), -1, 'stale connkey must be gone');
  assert.ok(out.indexOf('auth=fresh99') !== -1);
  assert.ok(out.indexOf('connkey=123456') !== -1);
});
test('appends auth when the url has none', () => {
  assert.strictEqual(
      ZM.rebuildStreamSrc('cgi-bin/nph-zms?monitor=35&mode=jpeg', 'abc', null),
      'cgi-bin/nph-zms?monitor=35&mode=jpeg&auth=abc');
});
test('appends auth with ? when no query string present', () => {
  assert.strictEqual(
      ZM.rebuildStreamSrc('cgi-bin/nph-zms', 'abc', null),
      'cgi-bin/nph-zms?auth=abc');
});
test('leaves connkey untouched when none requested', () => {
  assert.strictEqual(
      ZM.rebuildStreamSrc('cgi-bin/nph-zms?auth=OLD&connkey=42', 'NEW'),
      'cgi-bin/nph-zms?auth=NEW&connkey=42');
});
test('handles empty/undefined src safely', () => {
  assert.strictEqual(ZM.rebuildStreamSrc('', 'abc', 7), '?auth=abc&connkey=7');
  assert.strictEqual(ZM.rebuildStreamSrc(undefined, 'abc', null), '?auth=abc');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
