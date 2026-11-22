'use strict';

// A failed download request (HTTP error) only cleared the ticker in the success
// handler, so the "Downloading" heading and the ticker dots ran for ever with
// no error shown to the user.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.SNAPSHOT_JS ||
  path.join(__dirname, '../../web/skins/classic/views/js/snapshot.js');
const src = fs.readFileSync(srcPath, 'utf8');

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

function load() {
  const state = {cleared: [], intervals: [], tickerText: null, handlers: {},
    ajaxFails: []};

  function chainable() {
    const obj = {
      length: 0,
      prop: () => obj,
      addClass: () => obj,
      removeClass: () => obj,
      modal: () => obj,
      text: (v) => (v === undefined ? '' : obj),
      ready: (fn) => {
        if (fn) fn();
        return obj;
      },
    };
    return obj;
  }

  const $j = function(sel) {
    const obj = chainable();
    if (typeof sel === 'string' && sel === '#downloadProgressTicker') {
      obj.text = function(v) {
        if (v === undefined) return state.tickerText || '';
        state.tickerText = String(v);
        return obj;
      };
    }
    return obj;
  };

  $j.getJSON = function() {
    const req = {
      done(cb) {
        req.doneCb = cb;
        return req;
      },
      fail(cb) {
        req.failCb = cb;
        return req;
      },
    };
    state.req = req;
    return req;
  };

  const sandbox = {
    $j: $j,
    console: {log() {}, warn() {}, error() {}},
    thisUrl: 'index.php',
    canView: {Snapshots: true},
    canEdit: {Snapshots: true},
    snapshot: {Id: 4, EventIds: [10, 11]},
    downloadProgressString: 'Downloading',
    downloadSucceededString: 'Download Succeeded',
    downloadFailedString: 'Download failed',
    bindButton(sel, ev, data, fn) {
      state.handlers[sel] = fn;
    },
    logAjaxFail(...args) {
      state.ajaxFails.push(args);
    },
    setInterval(fn, ms) {
      state.intervals.push(ms);
      return 42;
    },
    clearInterval(id) {
      state.cleared.push(id);
    },
    setTimeout() {},
    document: {
      referrer: '',
      getElementById: () => ({addEventListener() {}}),
    },
    window: {location: {assign() {}, replace() {}}, history: {back() {}}},
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'snapshot.js'});
  return {sandbox, state};
}

console.log('snapshot download failure');

// jQuery invokes the fail callback asynchronously, after the click handler has
// assigned the ticker id, so the callback is driven here rather than from
// inside the stub (which would run before setInterval() returns).
function fail(state) {
  assert.ok(state.req && state.req.failCb, 'the download request must have a fail handler');
  state.req.failCb({statusText: 'err', status: 500}, 'error', 'x');
}

test('the ticker is stopped when the request fails', () => {
  const {state} = load();
  assert.ok(state.handlers['#downloadBtn'], 'the download button must be bound');
  state.handlers['#downloadBtn']({preventDefault() {}});
  assert.deepStrictEqual(state.intervals, [500], 'the ticker interval must have been started');
  fail(state);
  assert.ok(state.cleared.includes(42),
      'clearInterval must be called with the ticker id, else it runs for ever');
});

test('and the user is told the download failed', () => {
  const {state} = load();
  state.handlers['#downloadBtn']({preventDefault() {}});
  fail(state);
  assert.strictEqual(state.tickerText, 'Download failed',
      'the ticker must be replaced with downloadFailedString');
});

test('the failure is still logged', () => {
  const {state} = load();
  state.handlers['#downloadBtn']({preventDefault() {}});
  fail(state);
  assert.strictEqual(state.ajaxFails.length, 1);
  assert.deepStrictEqual(state.ajaxFails[0].slice(1), ['error', 'x'],
      'the three callback arguments must be forwarded to logAjaxFail');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
