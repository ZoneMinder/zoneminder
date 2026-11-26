'use strict';

// The snapshot view's Delete answers {result: 'Ok', message: [...]} when the
// server could not delete the snapshot (the message lists the problems). The
// handler only looked at result, so it went to the snapshots list as if the
// snapshot was gone. It must stay on the page and show the message.
// Run as: node tests/js/snapshot-delete-failed.test.js [path/to/snapshot.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/js/snapshot.js');
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
  const state = {assigned: [], alerts: [], listeners: {}, req: null};
  const chain = () => {
    const obj = {length: 0, prop: () => obj, modal: () => obj, text: () => obj, ready: () => obj};
    return obj;
  };
  const $j = function() {
    return chain();
  };
  $j.getJSON = function() {
    const req = {
      done(cb) {
        req.doneCb = cb;
        return req;
      },
      fail() {
        return req;
      },
    };
    state.req = req;
    return req;
  };
  const sandbox = {
    $j,
    console: {log() {}, warn() {}, error() {}},
    thisUrl: 'index.php',
    canView: {Snapshots: true},
    canEdit: {Snapshots: true},
    snapshot: {Id: 4, EventIds: [10]},
    bindButton() {},
    enoperm() {},
    logAjaxFail() {},
    alert(m) {
      state.alerts.push(m);
    },
    document: {
      referrer: '',
      getElementById: (id) => ({
        addEventListener(ev, fn) {
          state.listeners[id] = fn;
        },
      }),
    },
    window: {location: {assign(u) {
      state.assigned.push(u);
    }}, history: {back() {}}},
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'snapshot.js'});
  vm.runInContext('manageDelConfirmModalBtns()', sandbox);
  state.listeners.delConfirmBtn({preventDefault() {}});
  return state;
}

console.log('snapshot view delete');

test('a refused delete stays on the page and shows the message', () => {
  const state = load();
  state.req.doneCb({result: 'Ok', message: ['Snapshot 4 could not be deleted.']});
  assert.deepStrictEqual(state.assigned, [], 'must not leave for the snapshots list');
  assert.deepStrictEqual(state.alerts, ['Snapshot 4 could not be deleted.']);
});

test('a successful delete goes to the snapshots list', () => {
  const state = load();
  state.req.doneCb({result: 'Ok', message: []});
  assert.deepStrictEqual(state.assigned, ['?view=snapshots']);
  assert.deepStrictEqual(state.alerts, []);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
