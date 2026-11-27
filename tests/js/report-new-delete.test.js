'use strict';

// The report page of a new, unsaved report has no id. Its Delete button was
// enabled anyway, and confirming sent ids[]= with nothing in it, so the user
// got a "Report not found." alert.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/report.js');
const src = fs.readFileSync(file, 'utf8');

function deleteDisabledFor(reportId) {
  const buttons = {};
  const button = () => ({disabled: true, ready() {}, prop(k, v) {
    this[k] = v;
  }});
  const el = () => ({addEventListener() {}, getAttribute: () => reportId});
  const sandbox = {
    $j: (sel) => buttons[sel] || (buttons[sel] = button()),
    canEdit: {Events: true}, canView: {Events: true},
    document: {referrer: '', getElementById: el}, window: {}, console,
    thisUrl: '?', enoperm() {}, getDelConfirmModal() {},
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  vm.runInContext('initPage()', sandbox);
  return buttons['#deleteBtn'].disabled;
}

assert.strictEqual(deleteDisabledFor(''), true, 'delete is disabled for a new report');
assert.strictEqual(deleteDisabledFor('5'), false, 'delete is enabled for a saved report');
console.log('ok - report delete is disabled until the report has an id');
