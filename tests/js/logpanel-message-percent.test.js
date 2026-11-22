'use strict';

// Log messages are stored and sent as logged, never percent-encoded, but the
// log panel ran them through decodeURIComponent. A logged URL lost its %XX
// escapes, and a %XX that is not valid UTF-8 threw, so the message kept no
// event link.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.LOGPANEL_JS ||
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');
const start = src.indexOf('function processRows(rows) {');
const end = src.indexOf('\n  }\n', start);
assert.ok(start >= 0 && end > start, 'processRows not found in logpanel.js');

const sandbox = {
  console: {log() {}},
  $j: {each: (rows, fn) => rows.forEach((row, i) => fn(i, row))},
};
vm.createContext(sandbox);
vm.runInContext(src.slice(start, end + 4), sandbox);

function shown(message) {
  return sandbox.processRows([{Message: message}])[0].Message;
}

assert.strictEqual(shown('GET http://cam/cgi-bin/snap.cgi?name=front%20door%2Fleft failed'),
    'GET http://cam/cgi-bin/snap.cgi?name=front%20door%2Fleft failed',
    'a logged URL keeps its escapes');
assert.strictEqual(shown('event 42 written to /data/caf%E9'),
    '<a href="?view=event&eid=42">event 42</a> written to /data/caf%E9',
    'a %XX that is not UTF-8 still gets its event link');
assert.strictEqual(shown('Disk 95% full, event 7'),
    'Disk 95% full, <a href="?view=event&eid=7">event 7</a>',
    'a bare % is unchanged');
assert.strictEqual(shown('&lt;b&gt; event 3'),
    '&lt;b&gt; <a href="?view=event&eid=3">event 3</a>',
    'escaped markup from the server stays escaped');
console.log('ok - log panel shows messages with % as logged');
