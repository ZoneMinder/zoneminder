'use strict';

// A merged timeline bar lists eventId:frameId per slot in data-slot-targets. Click
// and hover must use the slot under the pointer, not the leftmost event.
//
// node tests/js/timeline-merged-slot-target.test.js [path/to/timeline.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/timeline.js');
const src = fs.readFileSync(file, 'utf8');

const location = {href: ''};
const requested = [];
const sandbox = {
  $j: () => ({ready: () => {}}),
  document: {},
  window: {location},
  filterQuery: '',
  events: {},
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
// previewEvent asks the server for an event it does not have yet
sandbox.requestFrameData = (e, f) => requested.push(e + ':' + f);

function bar(attrs) {
  return {
    getAttribute: (n) => (n in attrs ? attrs[n] : null),
    getBoundingClientRect: () => ({left: 100, width: 9}),
  };
}
const merged = bar({'data-event-id': '10', 'data-frame-id': '1',
  'data-slot-targets': '10:1,11:4,12:1'});

sandbox.showEvent(merged, {clientX: 101});
assert.strictEqual(location.href, '?view=event&eid=10&fid=1');
sandbox.showEvent(merged, {clientX: 104});
assert.strictEqual(location.href, '?view=event&eid=11&fid=4');
sandbox.showEvent(merged, {clientX: 108});
assert.strictEqual(location.href, '?view=event&eid=12&fid=1');

sandbox.previewEvent(merged, {clientX: 108});
assert.deepStrictEqual(requested, ['12:1']);

// Plain bars and calls without a pointer keep the attributes
sandbox.showEvent(bar({'data-event-id': '7', 'data-frame-id': '2'}), {clientX: 103});
assert.strictEqual(location.href, '?view=event&eid=7&fid=2');
sandbox.showEvent(merged);
assert.strictEqual(location.href, '?view=event&eid=10&fid=1');
console.log('ok');
