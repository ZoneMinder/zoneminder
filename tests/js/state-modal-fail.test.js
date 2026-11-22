'use strict';

// stateStuff() shows "Please wait" and posts the run state change. When the
// request failed nothing hid it again or logged the failure, so the modal
// stayed stuck on "Please wait".

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.SKIN_JS ||
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const match = src.match(/function stateStuff\(action, runState, newState\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'stateStuff() not found in skin.js');

const classes = {pleasewait: new Set(['hidden'])};
const logged = [];
let failCb = null;
let reloaded = false;
function $j(sel) {
  const id = sel.replace(/^#/, '');
  const set = classes[id] || (classes[id] = new Set());
  return {
    toggleClass(c) {
      set.has(c) ? set.delete(c) : set.add(c); return this;
    },
    addClass(c) {
      set.add(c); return this;
    },
    removeClass(c) {
      set.delete(c); return this;
    },
  };
}
$j.ajax = () => {
  const p = {
    done() {
      return p;
    },
    fail(cb) {
      failCb = cb; return p;
    },
  };
  return p;
};
const sandbox = {
  $j, thisUrl: '?',
  location: {reload: () => {
    reloaded = true;
  }},
  logAjaxFail: (jqxhr) => {
    assert.ok(jqxhr, 'logAjaxFail needs the request');
    logged.push(jqxhr);
  },
};
vm.createContext(sandbox);
vm.runInContext(match[0], sandbox);

sandbox.stateStuff('save', undefined, 'x');
assert.ok(!classes.pleasewait.has('hidden'), 'Please wait is shown while saving');
assert.ok(failCb, 'a failed request is handled');
failCb({status: 500, statusText: 'Internal Server Error'}, 'error', 'x');
assert.ok(classes.pleasewait.has('hidden'), 'Please wait is hidden again after a failure');
assert.strictEqual(logged.length, 1, 'the failure is logged');
assert.ok(!reloaded, 'the page is not reloaded on failure');
console.log('ok - stateStuff recovers from a failed request');
