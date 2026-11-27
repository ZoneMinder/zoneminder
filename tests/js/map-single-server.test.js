'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');

let passed = 0;
let failed = 0;
async function test(name, fn) {
  try {
    await fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

async function scenario(monitors) {
  const markers = [];
  const els = {};
  const el = (id) => els[id] || (els[id] = {style: {}, innerHTML: '', classList: {add() {}}});
  const L = {
    fit: null,
    map: () => ({invalidateSize() {}, fitBounds(b) {
      L.fit = b;
    }}),
    latLng: (a, b) => ({a, b}),
    tileLayer: () => ({addTo() {}}),
    icon: (o) => o,
    marker: (ll, o) => {
      const m = {ll, o, addTo() {
        markers.push(m);
        return m;
      }, bindPopup(p) {
        m.popup = p;
        return m;
      }};
      return m;
    },
  };
  const ctx = {
    L, document: {getElementById: el}, console: {log() {}},
    location: {port: '', protocol: 'http:', host: 'zm'},
    ZM_OPT_GEOLOCATION_TILE_PROVIDER: 'x', ZM_OPT_GEOLOCATION_ACCESS_TOKEN: 't',
    serverId: 0, thisUrl: 'index.php', zmAuth: {appendTo: (u) => u},
    sin: Math.sin, cos: Math.cos, sqrt: Math.sqrt, atan2: Math.atan2,
    deg2rad: (d) => d * Math.PI / 180,
  };
  vm.createContext(ctx);
  vm.runInContext(fs.readFileSync(path.join(root, 'web/js/Server.js'), 'utf8'), ctx);
  ctx.Servers = [new ctx.Server({Id: 0, Hostname: 'h', Protocol: 'http', PathToZMS: '/zms'})];
  ctx.Servers[0].getFromApi = () => Promise.resolve({json: () => Promise.resolve({monitors})});
  vm.runInContext(fs.readFileSync(path.join(root, 'web/js/Map.js'), 'utf8') + ';this.ZMMap = ZMMap', ctx);
  new ctx.ZMMap().addMonitors();
  await new Promise((r) => setTimeout(r, 20));
  return {markers, els, L};
}

const mon = (o, events = '3') => ({
  Monitor: Object.assign({Id: '1', Name: 'a', Latitude: '10.1', Longitude: '20.2', Width: 640, Height: 480, ServerId: null}, o),
  Monitor_Status: {CaptureFPS: '5', Status: 'Connected'},
  Event_Summary: {TotalEvents: events},
});

(async () => {
  await test('a monitor without a ServerId gets a usable stream url', async () => {
    const r = await scenario([mon({})]);
    assert.strictEqual(r.markers.length, 1);
    assert.ok(!/undefined/.test(r.markers[0].popup), r.markers[0].popup);
    assert.ok(/src="http:\/\/h\/zms\?/.test(r.markers[0].popup), r.markers[0].popup);
  });
  console.log('');
  console.log(passed + ' passed, ' + failed + ' failed');
  process.exit(failed ? 1 : 0);
})();
