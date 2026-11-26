'use strict';

// setCookie() wrote the value raw, so a typed filter value such as 'a;b' was cut
// at the ';' and a '%41' was decoded by PHP into 'A'. getCookie() must decode
// what setCookie() wrote and still accept an older unencoded value.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

function slice(source, startMarker, endMarker) {
  const start = source.indexOf(startMarker);
  assert.ok(start >= 0, 'missing ' + startMarker);
  return source.slice(start, source.indexOf(endMarker, start) + endMarker.length);
}

const jar = {value: ''};
const ctx = {document: {}, sessionCookies: [], Date, JSON, decodeURIComponent, encodeURIComponent};
Object.defineProperty(ctx.document, 'cookie', {
  get: () => jar.value,
  set: (v) => {
    // A browser keeps only name=value up to the first ';'
    jar.value = v.split(';')[0];
  },
});
vm.createContext(ctx);
vm.runInContext(slice(skin, 'function isJSON', '\n}\n'), ctx);
vm.runInContext(slice(skin, 'function setCookie', '\n}\n'), ctx);
vm.runInContext(slice(skin, 'function getCookie', '\n}\n'), ctx);

for (const value of ['plain', 'a;b', '100%41', 'x=y', 'café bar']) {
  ctx.setCookie('zmFilter_MonitorName', value);
  assert.strictEqual(ctx.getCookie('zmFilter_MonitorName'), value, 'round trip of ' + JSON.stringify(value));
}

ctx.setCookie('zmFilter_MonitorId', JSON.stringify([1, 2]));
assert.deepStrictEqual(Array.from(ctx.getCookie('zmFilter_MonitorId')), [1, 2]);

jar.value = 'zmFilter_Source=50%';
assert.strictEqual(ctx.getCookie('zmFilter_Source'), '50%', 'legacy value with a bare %');

console.log('ok');
