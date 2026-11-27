'use strict';

// ZM_GO2RTC_PATH is meant to be the go2rtc API base, which go2rtc serves under
// /api, with its websocket at /api/ws. The natural thing to type is the server
// address, http://host:1984, and with that every request went to the wrong
// place: /ws is a 404 and /streams is go2rtc's RTSPtoWeb compatibility endpoint,
// so streams were never registered or played. go2rtcApiUrl() accepts either form.

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

// Load the real go2rtcApiUrl() from skin.js.
const match = src.match(/function go2rtcApiUrl\(path\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'go2rtcApiUrl() not found in skin.js');
const sandbox = {URL};
vm.createContext(sandbox);
vm.runInContext(match[0] + '\nthis.go2rtcApiUrl = go2rtcApiUrl;', sandbox);
const api = (p) => sandbox.go2rtcApiUrl(p).href;

console.log('go2rtcApiUrl');
test('adds /api to a bare server address', () => {
  assert.strictEqual(api('http://localhost:1984'), 'http://localhost:1984/api');
  assert.strictEqual(api('http://localhost:1984/'), 'http://localhost:1984/api');
});
test('leaves a path that already ends in /api alone', () => {
  assert.strictEqual(api('http://localhost:1984/api'), 'http://localhost:1984/api');
  assert.strictEqual(api('http://localhost:1984/api/'), 'http://localhost:1984/api');
});
test('works behind a reverse proxy sub-path', () => {
  assert.strictEqual(api('https://host/go2rtc'), 'https://host/go2rtc/api');
  assert.strictEqual(api('https://host/go2rtc/api'), 'https://host/go2rtc/api');
});
test('keeps credentials in the URL', () => {
  assert.strictEqual(api('http://user:pass@cam:1984'), 'http://user:pass@cam:1984/api');
});
test('the websocket the callers build lands on /api/ws', () => {
  const url = sandbox.go2rtcApiUrl('http://localhost:1984');
  url.protocol = 'ws:';
  url.pathname += '/ws';
  assert.strictEqual(url.href, 'ws://localhost:1984/api/ws');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
