'use strict';

// ZM_RTSP2WEB_PATH may carry a path prefix when RTSP2Web sits behind a reverse
// proxy, e.g. https://host/rtsp2web. zmc keeps that prefix when it talks to
// RTSP2Web, but the browser used to replace the pathname with "/stream/...", so
// it requested https://host/stream/5/... and got a 404. rtsp2webUrl() appends the
// stream path to the configured base path instead of replacing it.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

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

// Load the real rtsp2webUrl() from skin.js.
const match = src.match(/function rtsp2webUrl\(base, suffix\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'rtsp2webUrl() not found in skin.js');
const sandbox = {URL};
vm.createContext(sandbox);
vm.runInContext(match[0] + '\nthis.rtsp2webUrl = rtsp2webUrl;', sandbox);
const api = (base, suffix) => sandbox.rtsp2webUrl(base, suffix).href;

console.log('rtsp2webUrl');
test('keeps a reverse proxy path prefix', () => {
  assert.strictEqual(
      api('https://h/rtsp2web', '/stream/5/channel/0/webrtc'),
      'https://h/rtsp2web/stream/5/channel/0/webrtc');
});
test('does not double the slash on a base with a trailing slash', () => {
  assert.strictEqual(
      api('https://h/rtsp2web/', '/stream/5/channel/0/webrtc'),
      'https://h/rtsp2web/stream/5/channel/0/webrtc');
});
test('works without a path prefix', () => {
  assert.strictEqual(
      api('http://h:8083', '/stream/5/channel/0/webrtc'),
      'http://h:8083/stream/5/channel/0/webrtc');
});
test('drops credentials from the base', () => {
  assert.strictEqual(
      api('http://u:p@h:8083', '/stream/5/channel/0/webrtc'),
      'http://h:8083/stream/5/channel/0/webrtc');
});
test('a caller can still set the websocket scheme and query', () => {
  const url = sandbox.rtsp2webUrl('https://h/rtsp2web',
      '/stream/5/channel/1/mse');
  url.protocol = 'wss';
  url.search = 'uuid=5&channel=1';
  assert.strictEqual(url.href,
      'wss://h/rtsp2web/stream/5/channel/1/mse?uuid=5&channel=1');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
