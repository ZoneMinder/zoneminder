'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
const src = fs.readFileSync(path.join(root, 'web/skins/classic/views/js/monitor.js'), 'utf8');
const dmsSrc = fs.readFileSync(path.join(root, 'web/js/dms.js'), 'utf8')
    .replace('export default class', 'class')
    .replace('export function', 'function')
    .replace('export class', 'class');

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

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  let depth = 0;
  for (let i = src.indexOf('{', start); ; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
}

function page(lat, lng) {
  const els = {
    'newMonitor[Latitude]': {id: 'newMonitor[Latitude]', value: lat},
    'newMonitor[Longitude]': {id: 'newMonitor[Longitude]', value: lng},
    'LatitudeDMS': {id: 'LatitudeDMS', value: ''},
    'LongitudeDMS': {id: 'LongitudeDMS', value: ''},
  };
  els.contentForm = {elements: {
    'newMonitor[Latitude]': els['newMonitor[Latitude]'],
    'newMonitor[Longitude]': els['newMonitor[Longitude]'],
  }};
  const view = {latlng: null};
  const ctx = {
    document: {getElementById: (id) => els[id]},
    console,
    L: {LatLng: function(a, b) {
      this.lat = Number(a);
      this.lng = Number(b);
    }},
    marker: {setLatLng(l) {
      view.latlng = l;
    }},
    map: {setView() {}, invalidateSize() {}},
    setTimeout: () => {},
    navigator: {geolocation: {getCurrentPosition: (cb) => cb({coords: {latitude: -33.8688, longitude: 151.2093}})}},
  };
  vm.createContext(ctx);
  const fns = ['ll2dms', 'dms2ll', 'updateMarker', 'updateLatitudeAndLongitude', 'getLocation'];
  vm.runInContext(dmsSrc + fns.map(extract).join('\n') + ';this.api = {ll2dms, dms2ll, getLocation, parseDms}', ctx);
  return {els, view, api: ctx.api};
}

test('an empty or unparsable DMS field leaves the decimal value alone', () => {
  const p = page('10', '20');
  p.els.LatitudeDMS.value = '';
  p.api.dms2ll(p.els.LatitudeDMS);
  assert.strictEqual(p.els['newMonitor[Latitude]'].value, '10');
  p.els.LongitudeDMS.value = 'abc';
  p.api.dms2ll(p.els.LongitudeDMS);
  assert.strictEqual(p.els['newMonitor[Longitude]'].value, '20');
  assert.strictEqual(p.view.latlng, null);
});

test('a valid DMS value still sets the decimal value', () => {
  const p = page('10', '20');
  p.els.LatitudeDMS.value = '33°52′7.68″ S';
  p.api.dms2ll(p.els.LatitudeDMS);
  assert.strictEqual(p.els['newMonitor[Latitude]'].value, '-33.86880000');
});
console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
