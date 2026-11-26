'use strict';

// A failed frames query (500, or ajaxError() answering 200 with result=Error)
// left the table on "Loading, please wait" for ever, and a failed stats request
// left "Please wait. Loading from ajax request..." in the detail row.
// The devices On/Off key must be URL encoded.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function load(file) {
  const src = fs.readFileSync(path.join(__dirname, '../../web/skins/classic/views/js/' + file), 'utf8');
  const state = {tableCalls: [], alerts: [], urls: [], fail: null, done: null, ajaxFails: []};
  const chain = {
    bootstrapTable: (a) => {
      state.tableCalls.push(a);
      return chain;
    },
    on: () => chain, prop: () => chain, remove: () => chain, hide: () => chain,
    click: () => chain, ready: () => chain,
  };
  const $j = () => chain;
  const req = () => {
    const p = {
      done: (f) => {
        state.done = f;
        return p;
      },
      fail: (f) => {
        state.fail = f;
        return p;
      },
    };
    return p;
  };
  $j.getJSON = (u) => {
    state.urls.push(u);
    return req();
  };
  $j.get = $j.getJSON;
  $j.each = () => {};
  $j.map = () => [];
  $j.ready = () => {};
  const globals = {
    encodeURIComponent, $j, state, console: {log() {}}, thisUrl: '/zm/index.php', eid: 5,
    deferTableRequestWhileHidden: () => false,
    logAjaxFail: (...a) => state.ajaxFails.push(a),
    alert: (m) => state.alerts.push(m), canEdit: {Devices: true},
    document: {addEventListener() {}, referrer: ''},
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : t[p]),
    set: (t, p, v) => {
      t[p] = v;
      return true;
    },
  });
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, state};
}

let ok = true;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    ok = false;
    console.error('  FAIL ' + name + '\n    ' + e.message);
  }
}

test('frames: HTTP failure hides the loading indicator', () => {
  const {sandbox, state} = load('frames.js');
  sandbox.ajaxRequest({data: {}, success() {}});
  state.fail({status: 500, responseText: ''});
  assert.ok(state.tableCalls.includes('hideLoading'));
});
test('frames: result=Error hides the loading indicator and says why', () => {
  const {sandbox, state} = load('frames.js');
  let succeeded = false;
  sandbox.ajaxRequest({data: {}, success() {
    succeeded = true;
  }});
  state.done({result: 'Error', message: 'denied'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.deepStrictEqual(state.alerts, ['denied']);
  assert.ok(!succeeded);
});
test('frames: failed detail request clears the wait text', () => {
  const {sandbox, state} = load('frames.js');
  let html = null;
  const $detail = {html: (h) => {
    html = h;
  }, empty: () => {
    html = '';
  }};
  sandbox.detailFormatter(0, {EventId: 1, FrameId: 2}, $detail);
  state.fail({status: 500, responseText: ''});
  assert.strictEqual(html, '');
});
test('devices: key is URL encoded', () => {
  const {sandbox, state} = load('devices.js');
  sandbox.processClicks({}, 'On', '', {KeyString: 'A&b=1#'}, null);
  assert.ok(state.urls[0].endsWith('&key=A%26b%3D1%23'), state.urls[0]);
});
process.exit(ok ? 0 : 1);
