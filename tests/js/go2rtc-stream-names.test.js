'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname, '../../web/js/go2rtc-stream-names.js'));

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

console.log('getGo2RTCStreamBase');
test('prefers monitor name when present', () => {
  assert.strictEqual(ZM.getGo2RTCStreamBase(42, 'Front Door'), 'Front Door');
});
test('falls back to monitor id when name is empty', () => {
  assert.strictEqual(ZM.getGo2RTCStreamBase(42, ''), '42');
});
test('falls back to monitor id when name is null', () => {
  assert.strictEqual(ZM.getGo2RTCStreamBase(42, null), '42');
});
test('returns empty string when neither id nor name are available', () => {
  assert.strictEqual(ZM.getGo2RTCStreamBase(null, null), '');
});

console.log('\ngetGo2RTCStreamName');
test('builds the primary stream name from the monitor name', () => {
  assert.strictEqual(ZM.getGo2RTCStreamName(42, 'Front Door', ''), 'Front Door');
});
test('builds suffixed stream names from the monitor name', () => {
  assert.strictEqual(
      ZM.getGo2RTCStreamName(42, 'Front Door', '_CameraDirectPrimary'),
      'Front Door_CameraDirectPrimary');
});
test('builds suffixed stream names from the monitor id when needed', () => {
  assert.strictEqual(
      ZM.getGo2RTCStreamName(42, '', '_CameraDirectPrimary'),
      '42_CameraDirectPrimary');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
