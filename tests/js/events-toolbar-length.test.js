'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/events.js'), 'utf8');

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

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  assert.ok(start >= 0, 'function ' + name + ' not found in events.js');
  let depth = 0;
  for (let i = src.indexOf('{', start); i < src.length; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
  throw new Error('Could not extract ' + name);
}

function row(over) {
  return Object.assign({
    Id: 1, Archived: 'No', Emailed: 'No', Name: 'E', Monitor: 'M', Cause: 'Motion',
    Notes: '', Frames: 1, AlarmFrames: 0, MaxScore: 0, DiskSpace: '1MB', Storage: 'Default',
    Length: 5,
  }, over);
}

function processEnv() {
  const ctx = {
    canView: {Events: true}, canEdit: {Events: true}, yesString: 'Yes', archivedString: 'A',
    emailedString: 'E', filterQuery: '', sortQuery: '', ZM_DATETIME_FORMAT_PATTERN: '',
    WEB_LIST_THUMBS: false, Date, String, Math, console: {log() {}},
    $j: {each: (a, f) => a.forEach((v, i) => f(i, v))},
  };
  vm.createContext(ctx);
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  return ctx;
}

console.log('Testing events.js toolbar and duration:');

test('Length of 25 hours is not wrapped to 01:00:00', () => {
  const ctx = processEnv();
  const r = row({Length: 90000});
  ctx.processRows([r]);
  assert.strictEqual(r.Length, '25:00:00');
});

test('Length formats short and fractional values', () => {
  const ctx = processEnv();
  const r = row({Length: '3725.7'});
  ctx.processRows([r]);
  assert.strictEqual(r.Length, '01:02:05');
});

test('buttons are disabled when the selection is empty after a reload', () => {
  const props = {};
  const btn = (n) => ({prop: (k, v) => {
    if (v !== undefined) props[n + '.' + k] = v;
  }});
  const ctx = {
    viewButton: btn('view'), archiveButton: btn('archive'), unarchiveButton: btn('unarchive'),
    editButton: btn('edit'), exportButton: btn('export'), downloadButton: btn('download'),
    deleteButton: btn('delete'), unarchiveString: 'Unarchive', yesString: 'Yes',
    canView: {Events: true}, canEdit: {Events: true},
    table: {bootstrapTable: () => []},
    getArchivedSelections: () => false,
  };
  vm.createContext(ctx);
  vm.runInContext(extract('updateToolbarButtons') + '; this.f = updateToolbarButtons;', ctx);
  ctx.f();
  assert.strictEqual(props['delete.disabled'], true);
  assert.strictEqual(props['export.disabled'], true);
});

test('toolbar buttons are refreshed after every table load', () => {
  const m = src.match(/table\.on\(([^;]*?),\s*updateToolbarButtons\)/);
  assert.ok(m, 'updateToolbarButtons is not bound to table events');
  assert.ok(m[1].indexOf('post-body.bs.table') >= 0 || m[1].indexOf('load-success.bs.table') >= 0);
});

test('archive success alerts on result=Error', () => {
  const i = src.indexOf("task=archive");
  const seg = src.slice(i, i + 500);
  assert.ok(/result == 'Error'/.test(seg), 'archive success does not check result');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
