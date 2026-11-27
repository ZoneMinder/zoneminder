'use strict';

// Clicking the event text panel must open the event shown in it. showEvent()
// reads data-event-id/data-frame-id from the clicked element, so the panel
// needs them as well as the image.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/timeline.js'), 'utf8');

const els = {};
function jq(sel) {
  const el = els[sel] || (els[sel] = {
    attrs: {},
    handlers: {},
    length: 1,
    getAttribute(n) {
      return n in this.attrs ? this.attrs[n] : null;
    },
    attr(n, v) {
      this.attrs[n] = String(v);
      return this;
    },
    show() {},
    off() {
      return this;
    },
    on(ev, fn) {
      this.handlers[ev] = fn;
      return this;
    },
  });
  return el;
}
const location = {href: ''};
const sandbox = {
  $j: (x) => (typeof x === 'string' ? jq(x) : {ready: () => {}}),
  document: {},
  window: {location},
  filterQuery: '',
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

sandbox.loadEventImage('index.php?view=image&eid=7&fid=5', {Id: 7, MonitorId: 3}, 5);

const panel = els['#eventData3'];
assert.ok(panel && panel.handlers.click, 'text panel click handler not bound');
panel.handlers.click.call(panel);
assert.strictEqual(location.href, '?view=event&eid=7&fid=5',
    'clicking the text panel opened ' + location.href);
console.log('ok - text panel click opens the displayed event');
