'use strict';

// Montage review reviews a time window across monitors but had no way to list
// the events in that window; users retyped the range in the Events filter. The
// Events button must open ?view=events with the same range the view's own
// filter and Download use: DateTime >= start and DateTime <= end. DateTime is
// the pseudo-attribute meaning "the event was running then" (an overlap test);
// StartDateTime would be a containment test and would drop the event that was
// already recording when the window opened.
//
// The monitor term is only added when the review is limited to a subset of the
// monitors the user can view. When every viewable monitor is shown there is no
// MonitorId term.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/montagereview.js'), 'utf8');

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  assert.ok(start >= 0, name + ' not found');
  let depth = 0;
  for (let i = src.indexOf('{', start); i < src.length; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && --depth === 0) return src.slice(start, i + 1);
  }
  throw new Error('unterminated ' + name);
}

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

// Run click_events() with the given window and monitor list and return the
// query parameters of the URL it navigated to.
function click(opts) {
  const sandbox = {
    console: {log() {}, warn() {}, error() {}},
    minTimeSecs: opts.minTimeSecs,
    maxTimeSecs: opts.maxTimeSecs,
    monitorPtr: opts.monitorPtr,
    numMonitors: opts.monitorPtr.length,
    // The real helper renders the server timezone; identity keeps the expected
    // values in the test readable.
    secs2dbstr: opts.secs2dbstr || ((secs) => String(secs)),
  };
  sandbox.window = {location: {}};
  vm.createContext(sandbox);
  vm.runInContext(
      extract('filterTermsQueryString') + '\n' + extract('click_events'), sandbox);
  sandbox.click_events();
  const location = sandbox.window.location;
  assert.strictEqual(typeof location, 'string', 'click_events did not navigate');
  if (opts.raw) return location;
  return parseTerms(location);
}

function parseTerms(url) {
  assert.ok(url.indexOf('?view=events') === 0, 'not an events URL: ' + url);
  const params = new URL(url, 'https://example.test/').searchParams;
  const terms = {};
  for (const [key, value] of params) {
    const m = key.match(/^filter\[Query\]\[terms\]\[(\d+)\]\[(\w+)\]$/);
    if (!m) continue;
    (terms[m[1]] = terms[m[1]] || {})[m[2]] = value;
  }
  return Object.keys(terms).sort().map((index) => terms[index]);
}

test('the link carries the reviewed window and the monitors shown', function() {
  const terms = click({
    minTimeSecs: 1700000000,
    maxTimeSecs: 1700003600,
    monitorPtr: [3, 5],
  });
  assert.deepStrictEqual(terms, [
    {attr: 'DateTime', op: '>=', val: '1700000000'},
    {cnj: 'and', attr: 'DateTime', op: '<=', val: '1700003600'},
    {cnj: 'and', attr: 'MonitorId', op: 'IN', val: '3,5'},
  ]);
});

test('dates are URL-encoded', function() {
  const url = click({
    minTimeSecs: 1700000000,
    maxTimeSecs: 1700003600,
    monitorPtr: [3],
    secs2dbstr: (secs) => (secs == 1700000000 ? '2023-11-14 22:13:20' : '2023-11-14 23:13:20'),
    raw: true,
  });
  assert.ok(url.indexOf(' ') === -1, 'raw space in ' + url);
  assert.ok(url.indexOf('2023-11-14%2022%3A13%3A20') !== -1, url);
  assert.deepStrictEqual(parseTerms(url)[0].val, '2023-11-14 22:13:20');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
