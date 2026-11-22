'use strict';

// ajax/reports.php now lists refused report deletes in data.message. The
// reports list and the single report page ignored the reply, so a report the
// user can't delete looked deleted (the report page went back to the list
// with it still there).

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function deleteReportsFrom(file) {
  const src = fs.readFileSync(path.join(__dirname, '../../web/skins/classic/views/js/', file), 'utf8');
  const m = src.match(/function deleteReports\(ids\) \{[\s\S]*?\n\}\n/);
  assert.ok(m, 'deleteReports not found in ' + file);
  return m[0];
}

function run(file, reply) {
  const seen = {alerts: [], navigations: 0};
  const $j = () => ({bootstrapTable: () => seen.navigations++, modal() {}});
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
  const location = {reload: () => seen.navigations++, assign: () => seen.navigations++};
  const sandbox = {
    $j, thisUrl: '?', logAjaxFail() {}, console: {log() {}},
    alert: (m) => seen.alerts.push(m), window: {location, alert: (m) => seen.alerts.push(m)},
    document: {getElementById: () => ({innerHTML: ''})},
  };
  vm.createContext(sandbox);
  vm.runInContext(deleteReportsFrom(file), sandbox);
  vm.runInContext('deleteReports(["5"])', sandbox);
  return seen;
}

for (const file of ['reports.js', 'report.js']) {
  let r = run(file, {result: 'Ok', message: ['You do not have permission to delete report 5']});
  assert.deepStrictEqual([...r.alerts], ['You do not have permission to delete report 5'],
      file + ': a refused report delete is reported');
  r = run(file, {result: 'Error', message: 'Insufficient permissions for user x'});
  assert.deepStrictEqual([...r.alerts], ['Insufficient permissions for user x'], file + ': a refusal is reported');
  r = run(file, {result: 'Ok', message: []});
  assert.strictEqual(r.alerts.length, 0, file + ': a real delete says nothing');
  assert.ok(r.navigations > 0, file + ': and still refreshes');
}
console.log('ok - report deletes report refusals');
