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

console.log('authHashFromRelay');
test('extracts the hash from a hashed relay', () => {
  assert.strictEqual(ZM.authHashFromRelay('auth=abc123&user=plaza'), 'abc123');
});
test('extracts the hash when auth is not the first parameter', () => {
  assert.strictEqual(ZM.authHashFromRelay('user=plaza&auth=abc123'), 'abc123');
});
test('extracts the hash from a relay with no user', () => {
  assert.strictEqual(ZM.authHashFromRelay('auth=abc123'), 'abc123');
});
test('does not match a parameter merely ending in auth', () => {
  assert.strictEqual(ZM.authHashFromRelay('xauth=abc123'), '');
});
test('returns empty for the plain/none relay forms', () => {
  assert.strictEqual(ZM.authHashFromRelay('username=plaza&password=secret'), '');
  assert.strictEqual(ZM.authHashFromRelay('username=plaza'), '');
});
test('handles empty/undefined relay safely', () => {
  assert.strictEqual(ZM.authHashFromRelay(''), '');
  assert.strictEqual(ZM.authHashFromRelay(undefined), '');
  assert.strictEqual(ZM.authHashFromRelay(null), '');
});

console.log('appendQuery');
test('joins with ? when the url has no query string', () => {
  assert.strictEqual(ZM.appendQuery('/zm/index.php', 'auth=abc'), '/zm/index.php?auth=abc');
});
test('joins with & when the url already has a query string', () => {
  assert.strictEqual(ZM.appendQuery('/zm/index.php?view=montage', 'auth=abc'), '/zm/index.php?view=montage&auth=abc');
});
test('leaves the url untouched when auth is off', () => {
  // The `x ? '&'+x : ''` dance at every call site existed to avoid a dangling
  // separator; appendQuery owns that decision now.
  assert.strictEqual(ZM.appendQuery('/zm/index.php?view=montage', ''), '/zm/index.php?view=montage');
  assert.strictEqual(ZM.appendQuery('/zm/index.php', undefined), '/zm/index.php');
});

console.log('setUrlParam');
test('replaces an existing parameter in place', () => {
  assert.strictEqual(
      ZM.setUrlParam('nph-zms?monitor=26&auth=OLD&mode=jpeg', 'auth', 'NEW'),
      'nph-zms?monitor=26&auth=NEW&mode=jpeg');
});
test('appends when the parameter is absent', () => {
  assert.strictEqual(ZM.setUrlParam('nph-zms?monitor=26', 'auth', 'NEW'), 'nph-zms?monitor=26&auth=NEW');
});
test('does not match a parameter merely ending in the name', () => {
  assert.strictEqual(ZM.setUrlParam('nph-zms?xauth=OLD', 'auth', 'NEW'), 'nph-zms?xauth=OLD&auth=NEW');
});
test('replaces an empty-valued parameter', () => {
  assert.strictEqual(ZM.setUrlParam('nph-zms?auth=&mode=jpeg', 'auth', 'NEW'), 'nph-zms?auth=NEW&mode=jpeg');
});

console.log('ZMAuth');
test('derives the hash from the relay', () => {
  assert.strictEqual(new ZM.ZMAuth('auth=abc123&user=plaza').hash, 'abc123');
});
test('has no hash under the plain relay form', () => {
  assert.strictEqual(new ZM.ZMAuth('username=plaza&password=secret').hash, '');
});
test('has no hash when authentication is off', () => {
  assert.strictEqual(new ZM.ZMAuth('').hash, '');
  assert.strictEqual(new ZM.ZMAuth().hash, '');
});
test('the hash cannot drift from the relay', () => {
  // The regression this whole type exists for. ajax/stream.php omitted `auth`
  // from its reply whenever it matched the hash the request carried (which came
  // from auth_relay), so the separate auth_hash global was never corrected and
  // reconnecting streams baked it in. There is now one value, so an update to
  // the relay is by construction an update to the hash.
  const auth = new ZM.ZMAuth('auth=7361222c&user=plaza');
  auth.update({auth_relay: 'auth=5bc52e6d&user=plaza'});
  assert.strictEqual(auth.hash, '5bc52e6d');
});
test('update reports whether the credential changed', () => {
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(auth.update({auth_relay: 'auth=abc&user=plaza'}), false, 'same relay is not a change');
  assert.strictEqual(auth.update({auth_relay: 'auth=def&user=plaza'}), true);
  assert.strictEqual(auth.hash, 'def');
});
test('update accepts a reply carrying only auth', () => {
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(auth.update({auth: 'def'}), true);
  assert.strictEqual(auth.relay, 'auth=def&user=plaza', 'user must survive the swap');
  assert.strictEqual(auth.update({auth: 'def'}), false);
});
test('update ignores empty and missing payloads', () => {
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(auth.update(null), false);
  assert.strictEqual(auth.update({}), false);
  assert.strictEqual(auth.relay, 'auth=abc&user=plaza');
});
test('appendTo authenticates a url', () => {
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(
      auth.appendTo('/zm/index.php?view=request&request=status'),
      '/zm/index.php?view=request&request=status&auth=abc&user=plaza');
});
test('appendTo is a no-op when authentication is off', () => {
  assert.strictEqual(new ZM.ZMAuth('').appendTo('/zm/index.php?view=montage'), '/zm/index.php?view=montage');
});
test('applyTo swaps the hash and keeps the other stream options', () => {
  const auth = new ZM.ZMAuth('auth=5bc52e6d&user=plaza');
  assert.strictEqual(
      auth.applyTo('cgi-bin/nph-zms?monitor=26&auth=7361222c&user=plaza&connkey=563525&scale=25&mode=jpeg'),
      'cgi-bin/nph-zms?monitor=26&auth=5bc52e6d&user=plaza&connkey=563525&scale=25&mode=jpeg');
});
test('applyTo swaps the connkey too when reconnecting', () => {
  const auth = new ZM.ZMAuth('auth=5bc52e6d&user=plaza');
  const out = auth.applyTo('cgi-bin/nph-zms?monitor=26&auth=7361222c&user=plaza&connkey=563525&mode=jpeg', 563155);
  assert.strictEqual(out.indexOf('auth=7361222c'), -1, 'stale hash must be gone');
  assert.strictEqual(out.indexOf('connkey=563525'), -1, 'dead connkey must be gone');
  assert.ok(out.indexOf('auth=5bc52e6d') !== -1);
  assert.ok(out.indexOf('connkey=563155') !== -1);
});
test('applyTo appends the whole relay when the src carries no auth', () => {
  // user= has to come along, otherwise zms falls back to scanning every row.
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(
      auth.applyTo('cgi-bin/nph-zms?monitor=26&mode=jpeg'),
      'cgi-bin/nph-zms?monitor=26&mode=jpeg&auth=abc&user=plaza');
});
test('applyTo appends the plain relay form, which has no hash to swap', () => {
  const auth = new ZM.ZMAuth('username=plaza&password=secret');
  assert.strictEqual(
      auth.applyTo('cgi-bin/nph-zms?monitor=26&mode=jpeg'),
      'cgi-bin/nph-zms?monitor=26&mode=jpeg&username=plaza&password=secret');
});
test('applyTo returns empty for a blank src rather than a bare query string', () => {
  // montagereview's loadImage2Monitor treats '' as "nothing to load"; a bare
  // '?auth=...' would resolve against the page and load HTML as an image.
  const auth = new ZM.ZMAuth('auth=abc&user=plaza');
  assert.strictEqual(auth.applyTo(''), '');
  assert.strictEqual(auth.applyTo(undefined), '');
  assert.strictEqual(auth.applyTo('', 99), '');
});
test('applyTo only sets the connkey when authentication is off', () => {
  const auth = new ZM.ZMAuth('');
  assert.strictEqual(
      auth.applyTo('cgi-bin/nph-zms?monitor=26&connkey=1&mode=jpeg', 99),
      'cgi-bin/nph-zms?monitor=26&connkey=99&mode=jpeg');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
