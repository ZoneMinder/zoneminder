'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

function extract(startMarker, endMarker) {
  const start = src.indexOf(startMarker);
  assert.ok(start >= 0, 'missing ' + startMarker);
  const end = src.indexOf(endMarker, start);
  return src.slice(start, end + endMarker.length);
}

// The version action endpoint answers with HTML (no view file), so choosing a
// reminder option must reload the page rather than fail JSON parsing.
let handler = null;
let reloaded = false;
let failed = false;
const chain = (succeed) => {
  const p = {
    done: (fn) => {
      if (succeed) fn('<html></html>');
      return p;
    },
    fail: (fn) => {
      if (!succeed) fn();
      return p;
    },
  };
  return p;
};
const $j = function() {
  return {click: (fn) => {
    handler = fn;
  }, data: () => 'hour'};
};
// getJSON parses the body as JSON, so an HTML reply makes it fail.
$j.getJSON = () => chain(false);
let posted = null;
$j.get = () => chain(true);
// index.php ignores an action sent by GET, so the choice must be POSTed.
$j.post = (url, data) => {
  posted = {url, data};
  return chain(true);
};
const ctx = {
  $j, thisUrl: 'index.php', console,
  logAjaxFail: () => {
    failed = true;
  },
  window: {location: {reload: () => {
    reloaded = true;
  }}},
};
vm.createContext(ctx);
vm.runInContext(extract('function reminderClickFunction', '\n}\n'), ctx);
ctx.reminderClickFunction();
handler.call({});
assert.ok(reloaded, 'page was not reloaded after choosing a version reminder');
assert.ok(!failed, 'version reminder request was treated as a failure');
console.log('  ok version reminder reloads on an HTML reply');
assert.ok(posted, 'version reminder choice was not sent by POST');
assert.strictEqual(posted.data.action, 'version');
assert.strictEqual(posted.data.option, 'hour');
console.log('  ok version reminder choice is POSTed with the action');
