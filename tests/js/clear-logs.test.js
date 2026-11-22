'use strict';

// The Clear Logs button used to start disabled and only enable once rows were
// selected, so pressing it on a freshly loaded Log panel did nothing. Nothing
// selected now means clear all, sent as one request rather than 100 ids at a
// time, and a filter narrows that to the rows on screen.
// refs #4727

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/logpanel.js'), 'utf8');

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

// logpanel.js is a browser file. Resolve unknown globals to undefined and give
// real values only to what the helpers touch. The ready() stub drops the panel
// bootstrap, so initLogPanel() never runs.
function loadPanel() {
  const $j = function() {
    return {
      ready: () => {},
      each: () => {},
      find: () => ({length: 0}),
    };
  };
  const globals = {
    Object: Object, Array: Array, JSON: JSON, Math: Math, Date: Date,
    $j: $j,
    console: {log() {}, warn() {}, error() {}},
  };
  const sandbox = new Proxy(globals, {
    has: () => true, // every bare identifier resolves against this object
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'logpanel.js'});
  return sandbox;
}

const i18n = {
  'ConfirmClearLogs': 'selected?',
  'ConfirmClearAllLogs': 'all?',
  'ConfirmClearFilteredLogs': 'filtered?',
  'ClearLogs': 'Clear Logs',
  'ClearAllLogs': 'Clear ALL Logs',
  'ClearFilteredLogs': 'Clear Filtered Logs',
};

console.log('filter of the last query');
test('keeps the filter fields of a real query', () => {
  const filter = loadPanel().logPanelFilterOf({
    search: 'boom',
    sort: 'DateTime',
    order: 'asc',
    offset: 0,
    limit: 25,
    filter: '{"Message":"x"}',
    ServerId: '4',
    level: ['ERR'],
    Component: ['zmc'],
    StartDateTime: '2026-01-01 00:00:00',
    EndDateTime: '2026-01-02 00:00:00',
  });
  assert.deepStrictEqual(Object.keys(filter).sort(), [
    'Component', 'EndDateTime', 'ServerId', 'StartDateTime', 'filter', 'level', 'search',
  ]);
  assert.strictEqual(filter.search, 'boom');
  assert.strictEqual(filter.filter, '{"Message":"x"}');
  assert.strictEqual(filter.ServerId, '4');
  assert.deepStrictEqual(Array.from(filter.level), ['ERR']);
  assert.deepStrictEqual(Array.from(filter.Component), ['zmc']);
  assert.strictEqual(filter.StartDateTime, '2026-01-01 00:00:00');
  assert.strictEqual(filter.EndDateTime, '2026-01-02 00:00:00');
});
test('ignores empty values but keeps the rest', () => {
  const filter = loadPanel().logPanelFilterOf({
    search: '', ServerId: null, level: undefined, StartDateTime: '', Component: ['zmc'],
  });
  assert.deepStrictEqual(Object.keys(filter), ['Component']);
  assert.deepStrictEqual(Array.from(filter.Component), ['zmc']);
});
test('ignores paging, sorting and an empty query', () => {
  const sandbox = loadPanel();
  assert.deepStrictEqual(Object.keys(sandbox.logPanelFilterOf({
    sort: 'DateTime', order: 'asc', offset: 0, limit: 25,
  })), []);
});

console.log('\nbutton and confirmation text');
test('selected rows say clear logs', () => {
  const sandbox = loadPanel();
  assert.strictEqual(sandbox.logPanelClearText(2, true, i18n), 'Clear Logs');
  assert.strictEqual(sandbox.logPanelConfirmText(2, true, i18n), 'selected?');
});
test('no selection and no filter says clear all', () => {
  const sandbox = loadPanel();
  assert.strictEqual(sandbox.logPanelClearText(0, false, i18n), 'Clear ALL Logs');
  assert.strictEqual(sandbox.logPanelConfirmText(0, false, i18n), 'all?');
});
test('no selection under a filter says clear filtered', () => {
  const sandbox = loadPanel();
  assert.strictEqual(sandbox.logPanelClearText(0, true, i18n), 'Clear Filtered Logs');
  assert.strictEqual(sandbox.logPanelConfirmText(0, true, i18n), 'filtered?');
});

console.log('\ndelete request data');
test('clear all with no filter sends only all', () => {
  const data = loadPanel().logPanelDeleteData([], true, {});
  assert.deepStrictEqual(Object.keys(data), ['all']);
  assert.strictEqual(data.all, 1);
});
test('clear all with a filter sends all and the filter', () => {
  const data = loadPanel().logPanelDeleteData([], true,
      {level: ['ERR'], ServerId: '4'});
  assert.strictEqual(data.all, 1);
  assert.deepStrictEqual(Array.from(data.level), ['ERR']);
  assert.strictEqual(data.ServerId, '4');
  assert.deepStrictEqual(Object.keys(data).sort(), ['ServerId', 'all', 'level']);
});
test('ids are sent without all or the filter', () => {
  const data = loadPanel().logPanelDeleteData([1, 2], false, {ServerId: '4'});
  assert.deepStrictEqual(Array.from(data['ids[]']), [1, 2]);
  assert.deepStrictEqual(Object.keys(data), ['ids[]']);
  assert.ok(!('all' in data));
  assert.ok(!('ServerId' in data));
});

// There is no test for the ajaxError handler in deleteLogs: it lives inside
// initLogPanel, which needs a live bootstrap table.

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
