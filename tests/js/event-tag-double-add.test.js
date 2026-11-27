'use strict';

// Pressing Enter twice on a highlighted dropdown tag, or clicking it twice,
// before the first addtag reply arrived sent addtag twice and drew the tag's
// chip twice, because isDup() only sees chips that are already drawn.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(file, 'utf8');
const fn = src.match(/function addTag\(tag, buttonPressed = null\) \{[\s\S]*?\n\}\n/);
assert.ok(fn, 'addTag() not found in event.js');
const decl = src.match(/var addingTagIds[^\n]*\n/);

let requests = 0;
let pending = [];
let chips = [];
const $j = () => ({val() {}, blur() {}, focus() {}, hide() {}});
$j.getJSON = () => {
  requests++;
  const p = {
    done(cb) {
      pending.push(cb); return p;
    },
    fail() {
      return p;
    },
    always(cb) {
      pending.push(cb); return p;
    },
  };
  return p;
};
const sandbox = {
  $j,
  thisUrl: '?',
  canEdit: {Events: true},
  eventData: {Id: 5},
  availableTags: [{Id: 1, Name: 'person'}],
  selectedTags: [],
  logAjaxFail() {},
  isDup: (name) => chips.includes(name),
  formatTag: (tag) => chips.push(tag.Name),
};
vm.createContext(sandbox);
vm.runInContext('var selectedTags = [];\n' + (decl ? decl[0] : '') + fn[0], sandbox);

const tag = sandbox.availableTags[0];
vm.runInContext('addTag(availableTags[0], "Enter")', sandbox);
vm.runInContext('addTag(availableTags[0], "Enter")', sandbox);
pending.forEach((cb) => cb({result: 'Ok'}));
assert.strictEqual(requests, 1, 'one addtag request for a double submit');
assert.deepStrictEqual(chips, ['person'], 'one chip for a double submit');

// Once the request is over the tag can be added again (after being removed).
chips = []; pending = [];
vm.runInContext('addTag(availableTags[0])', sandbox);
assert.strictEqual(requests, 2, 'a later add is sent');
assert.ok(tag);
console.log('ok');
