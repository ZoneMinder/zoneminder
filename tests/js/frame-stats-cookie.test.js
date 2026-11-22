'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const frame = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js'), 'utf8');

function slice(source, startMarker, endMarker) {
  const start = source.indexOf(startMarker);
  assert.ok(start >= 0, 'missing ' + startMarker);
  return source.slice(start, source.indexOf(endMarker, start) + endMarker.length);
}

// The third argument of setCookie() is seconds, so the frame stats choice has to
// outlive a session by a wide margin rather than expire within the hour.
const writes = [];
const ctx = {document: {}, sessionCookies: [], Date, JSON};
Object.defineProperty(ctx.document, 'cookie', {
  get: () => '',
  set: (v) => writes.push(v),
});
vm.createContext(ctx);
vm.runInContext(slice(skin, 'function setCookie', '\n}\n'), ctx);
vm.runInContext('function getCookie() { return null; }', ctx);
vm.runInContext(slice(frame, 'function getFrameStatsCookie', '\n}\n'), ctx);

ctx.getFrameStatsCookie();
assert.strictEqual(writes.length, 1);
const cookie = writes[0];
let lifetime = Infinity;
const maxAge = /max-age=(\d+)/.exec(cookie);
const expires = /expires=([^;]+)/.exec(cookie);
if (maxAge) lifetime = parseInt(maxAge[1], 10);
if (expires) lifetime = Math.min(lifetime, (Date.parse(expires[1]) - Date.now()) / 1000);
assert.ok(lifetime > 86400 * 30, 'frame stats cookie lives only ' + Math.round(lifetime) + 's: ' + cookie);
console.log('  ok frame stats cookie outlives a month');
