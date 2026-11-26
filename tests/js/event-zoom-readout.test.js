'use strict';

// With a video.js event no zms status arrives, so #zoomValue was only ever set
// to '1' on event change and never followed the PanZoom scale: after Zoom In the
// image was 1.3x but the readout said 1.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(file, 'utf8');

function extract(name) {
  const m = src.match(new RegExp('^function ' + name + '\\(.*?^}', 'ms'));
  return m ? m[0] : '';
}

let readout = '1';
const sandbox = {
  $j: () => ({html: (v) => {
    readout = v;
  }}),
  // Covered by event-zoom-persist.test.js
  storeEventZoom: () => {},
};
vm.createContext(sandbox);
vm.runInContext(extract('panZoomEventPanzoomchange'), sandbox);

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name + '\n    ' + e.message);
    failed++;
  }
}

test('readout follows the PanZoom scale', () => {
  assert.strictEqual(typeof sandbox.panZoomEventPanzoomchange, 'function');
  sandbox.panZoomEventPanzoomchange({detail: {x: 0, y: 0, scale: 1.3498588075760032}});
  assert.strictEqual(readout, '1.3');
});

test('event change in the video.js branch does not reset the readout to 1', () => {
  const from = src.indexOf('CurEventDefVideoPath = null;');
  const body = src.slice(from, src.indexOf('zmsEventChanged(previousEvent);', from));
  assert.ok(body.includes("'#modeValue'"));
  assert.ok(!/#zoomValue'\)\.html\('1'\)/.test(body));
});

process.exit(failed ? 1 : 0);
