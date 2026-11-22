'use strict';

// Deleting an event from the watch page's event list ignored the reply, so
// an event that could not be deleted (archived, no edit rights, already gone)
// just stayed in the list with no message.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.WATCH_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js'), 'utf8');
const start = src.indexOf('function manageDelConfirmModalBtns');
const end = src.indexOf('function refresh_events_table');
assert.ok(start >= 0 && end > start, 'manageDelConfirmModalBtns not found in watch.js');
const helper = src.match(/function reportUndeletedEvents\(data\) \{[\s\S]*?\n\}\n/);

function confirmDelete(reply) {
  const seen = {alerts: [], refreshed: 0};
  const clicks = {};
  const $j = () => ({data: () => 5, modal() {}});
  $j.getJSON = () => {
    const p = {
      done(cb) {
        cb(reply); return p;
      },
      fail() {
        return p;
      },
    };
    return p;
  };
  const sandbox = {
    $j, thisUrl: '?', canEdit: {Events: true}, enoperm() {}, logAjaxFail() {},
    alert: (m) => seen.alerts.push(m),
    eventListTable: {bootstrapTable: () => seen.refreshed++},
    document: {getElementById: (id) => ({addEventListener: (t, f) => {
      clicks[id] = f;
    }})},
  };
  vm.createContext(sandbox);
  vm.runInContext((helper ? helper[0] : '') + src.slice(start, end), sandbox);
  vm.runInContext('manageDelConfirmModalBtns()', sandbox);
  clicks.delConfirmBtn({preventDefault() {}});
  return seen;
}

let r = confirmDelete({result: 'Ok', message: ['Event 5 is archived, cannot delete it.'], notDeleted: [5]});
assert.deepStrictEqual(r.alerts, ['Event 5 is archived, cannot delete it.'], 'an undeleted event is reported');
assert.strictEqual(r.refreshed, 1, 'and the list still refreshes');
r = confirmDelete({result: 'Error', message: 'Permission denied'});
assert.deepStrictEqual(r.alerts, ['Permission denied'], 'a refusal is reported');
r = confirmDelete({result: 'Ok', message: [], notDeleted: []});
assert.strictEqual(r.alerts.length, 0, 'a real delete says nothing');
console.log('ok - watch page reports events it could not delete');
