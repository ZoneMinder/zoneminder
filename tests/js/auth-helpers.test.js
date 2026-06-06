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

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
