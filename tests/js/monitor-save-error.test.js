'use strict';

// Save and Close hides #contentButtons before saving. When the save request
// failed (server error, timeout, dropped connection) the buttons stayed
// hidden, so the user could not retry without reloading the page.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.MONITOR_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js'), 'utf8');
const match = src.match(/function saveMonitorData\(href = ''\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'saveMonitorData() not found in monitor.js');

const calls = [];
let ajaxOpts = null;
function $j(sel) {
  const el = {
    fadeIn() {
      return el;
    },
    fadeOut() {
      return el;
    },
    serializeArray: () => [],
    serialize: () => '',
    modal() {
      return el;
    },
    show() {
      calls.push(sel + ':show'); return el;
    },
    hide() {
      calls.push(sel + ':hide'); return el;
    },
  };
  return el;
}
$j.ajax = (opts) => {
  ajaxOpts = opts;
};
const alerts = [];
const sandbox = {$j, alert: (m) => alerts.push(m), DOMParser: function() {}};
vm.createContext(sandbox);
vm.runInContext(match[0], sandbox);

sandbox.saveMonitorData('?view=console');
assert.ok(ajaxOpts && typeof ajaxOpts.error === 'function', 'the save has an error handler');
ajaxOpts.error({status: 500}, 'error', 'Internal Server Error');
assert.ok(calls.includes('#contentButtons:show'), 'the buttons are shown again after a failed save');
assert.strictEqual(alerts.length, 1, 'the user is told the save failed');
console.log('ok - a failed monitor save brings the buttons back');
