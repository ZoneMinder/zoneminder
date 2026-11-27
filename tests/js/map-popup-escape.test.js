'use strict';

// The map pin popup is set as HTML by Leaflet, so a monitor name must not
// reach bindPopup() as markup.
//
// Usage: node tests/js/map-popup-escape.test.js [path/to/Map.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] || path.join(__dirname, '..', '..', 'web', 'js', 'Map.js');
const popups = [];
const el = () => ({style: {}, innerHTML: ''});
const map = {addLayer() {}, invalidateSize() {}, fitBounds() {}};
const marker = {addTo() {
  return marker;
}, bindPopup(html) {
  popups.push(html);
  return marker;
}};
const monitors = {monitors: [{
  Monitor: {Id: '7', Name: '<img src=x onerror=alert(1)>', Latitude: '1', Longitude: '2', Width: '640', Height: '480', ServerId: '0'},
  Monitor_Status: {Status: 'Connected', CaptureFPS: '5'},
  Event_Summary: {TotalEvents: 3},
}]};

const sandbox = {
  console: {log() {}},
  L: {
    map: () => map,
    latLng: () => ({}),
    tileLayer: () => ({addTo() {}}),
    icon: () => ({}),
    marker: () => marker,
  },
  ZM_OPT_GEOLOCATION_TILE_PROVIDER: '',
  ZM_OPT_GEOLOCATION_ACCESS_TOKEN: '',
  Servers: {0: {}},
  serverId: 0,
  thisUrl: 'index.php',
  zmAuth: {appendTo: (u) => u},
  Server: class {
    urlToZMS() {
      return 'zms';
    }
    getFromApi() {
      return Promise.resolve({json: () => Promise.resolve(monitors)});
    }
  },
  document: {getElementById: el},
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(file, 'utf8') + '\nthis.ZMMap = ZMMap;', sandbox);

const zmmap = new sandbox.ZMMap({});
zmmap.addMonitors();
setTimeout(() => {
  assert.strictEqual(popups.length, 1, 'one pin popup was bound');
  assert(!popups[0].includes('<img src=x'), 'monitor name is not markup: ' + popups[0]);
  assert(popups[0].includes('&lt;img src=x onerror=alert(1)&gt;'), 'monitor name is shown as text');
  assert(popups[0].includes('Events: 3'));
  console.log('ok');
}, 50);
