'use strict';

// With unsaved boxes, the training editor's click guard asked "Discard
// changes?" for every link, including the frame browser's href="#" pagination.
// Answering OK cleared the dirty flag though nothing was discarded, so a later
// frame change then lost the boxes without asking.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TRAINING_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/training.js'), 'utf8');
const start = src.indexOf('this._navGuardHandler = function(e) {');
const end = src.indexOf('\n  };\n', start);
assert.ok(start >= 0 && end > start, '_navGuardHandler not found in training.js');
const fn = src.slice(start + 'this._navGuardHandler = '.length, end + 4);

const prompts = [];
const sandbox = {confirm: (m) => {
  prompts.push(m); return true;
}};
vm.createContext(sandbox);
const self = {dirty: true, translations: {}};
sandbox.self = self;
const handler = vm.runInContext('(' + fn + ')', sandbox);

function clickLink(href) {
  const link = {getAttribute: () => href};
  handler({target: {closest: () => link}, preventDefault() {}, stopPropagation() {}});
}

clickLink('#');
assert.strictEqual(prompts.length, 0, 'an in-page link does not ask');
assert.strictEqual(self.dirty, true, 'and keeps the unsaved changes marked');

clickLink('?view=console');
assert.strictEqual(prompts.length, 1, 'a link that leaves the page still asks');
console.log('ok - the unsaved changes guard ignores in-page links');
