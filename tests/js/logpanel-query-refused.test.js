'use strict';

// ajax/log.php refuses a log query (no System view) with HTTP 200 and
// result 'Error', and no rows/total. The log panel's success callback read
// data.rows.length anyway, so it threw on every refresh and showed NaN.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.LOGPANEL_JS ||
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');
const q = src.indexOf("request=log&task=query'");
assert.ok(q >= 0, 'log query not found in logpanel.js');
const start = src.indexOf('success: function(data) {', q);
const end = src.indexOf('\n      error: function(jqxhr) {', start);
assert.ok(start > q && end > start, 'query success callback not found');
const body = src.slice(start + 'success: '.length, end).replace(/,\s*$/, '');

function run(data) {
  const seen = {alerts: [], status: [], hidden: 0, rows: null};
  const sandbox = {
    startTime: 0, Date, i18n: {AJAXRequestError: 'AJAX error'},
    params: {success: (r) => {
      seen.rows = r;
    }},
    table: {bootstrapTable: (cmd) => {
      if (cmd === 'hideLoading') seen.hidden++;
    }},
    updateRequestStatus: (s) => seen.status.push(s),
    updateHeaderStats() {}, secsToTime: () => '0', processRows: (r) => r,
    zmAlert: (m) => seen.alerts.push(m),
  };
  vm.createContext(sandbox);
  vm.runInContext('(' + body + ')', sandbox)(data);
  return seen;
}

const refused = run({result: 'Error', message: 'Insufficient permissions to view log entries'});
assert.deepStrictEqual([...refused.alerts], ['Insufficient permissions to view log entries'], 'the refusal is shown');
assert.ok(refused.status.includes('error'), 'and the status says error');
assert.strictEqual(refused.hidden, 1, 'and the loading overlay is hidden');
assert.strictEqual(refused.rows, null, 'and no rows are handed to the table');

const ok = run({result: 'Ok', rows: [{Id: 1}], total: 1, totalNotFiltered: 1});
assert.strictEqual(JSON.stringify(ok.rows), JSON.stringify({total: 1, totalNotFiltered: 1, rows: [{Id: 1}]}),
    'rows still load');
console.log('ok - log panel handles a refused query');
