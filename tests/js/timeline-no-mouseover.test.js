'use strict';

// With ?mouseover=0 the activity/event divs carry no data-on-mouseover-this.
// Binding must skip it rather than throw, or the zoom divs never get bound.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/timeline.js'), 'utf8');

function el(attrs) {
  return {
    getAttribute: (n) => (n in attrs ? attrs[n] : null),
    onclick: null,
    onmouseover: null,
  };
}
const slot = el({'data-on-click-this': 'previewEvent'});
const zoom = el({'data-on-click': 'tlZoomBounds'});
const lists = {
  'div.event, div.activity': [slot],
  'div.event': [slot],
  'div.activity': [],
  'div.tlzoom': [zoom],
};

const sandbox = {
  $j: () => ({ready: () => {}}),
  document: {
    querySelectorAll: (sel) => ({forEach: (fn) => (lists[sel] || []).forEach(fn)}),
  },
};
sandbox.window = sandbox;
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
sandbox.previewEvent = () => {};
sandbox.tlZoomBounds = () => {};

sandbox.divDataOnClick();

assert.strictEqual(typeof slot.onclick, 'function', 'slot click not bound');
assert.strictEqual(typeof zoom.onclick, 'function', 'zoom divs not bound after a slot without mouseover');
console.log('ok - divDataOnClick copes with slots that have no mouseover handler');
