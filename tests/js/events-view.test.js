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

function createEnv(options = {}) {
  const state = {tableCalls: [], successData: null};
  const chainable = {
    bootstrapTable: function(action) {
      state.tableCalls.push(action); return chainable;
    },
    on: () => chainable, one: () => chainable, ready: () => chainable, each: () => chainable,
    attr: () => '', val: () => '', prop: () => chainable, find: () => chainable,
    html: () => chainable, text: () => '', css: () => chainable, length: 0,
    show: () => chainable,
  };
  const $j = function() {
    return chainable;
  };
  $j.each = (arr, fn) => {
    if (!arr) return arr;
    arr.forEach((v, i) => fn(i, v));
    return arr;
  };
  $j.map = (arr, fn) => (arr || []).map(fn);

  const ctx = {
    $j,
    canView: {Events: options.canViewEvents ?? true},
    canEdit: {Events: options.canEditEvents ?? false, Monitors: options.canEditMonitors ?? false},
    yesString: 'Yes',
    archivedString: 'Archived',
    emailedString: 'Emailed',
    filterQuery: '',
    sortQuery: '',
    ZM_DATETIME_FORMAT_PATTERN: '',
    WEB_LIST_THUMBS: false,
    Date,
    String,
    Math,
    console: {log() {}, warn() {}, error() {}, debug() {}},
  };
  vm.createContext(ctx);
  return {ctx, state};
}

console.log('Testing web/skins/classic/views/js/events.js:');

test('processRows handles row with null Notes without throwing TypeError', () => {
  const {ctx} = createEnv();
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  const row = {
    Id: 1, Archived: 'No', Emailed: 'No', Name: 'Event 1', Monitor: 'Front', Cause: 'Motion',
    Notes: null, Frames: 10, AlarmFrames: 2, MaxScore: 10, DiskSpace: '1MB', Storage: 'Default',
    Length: 5,
  };
  const result = ctx.processRows([row]);
  assert.strictEqual(result.length, 1);
  assert.ok(row.Cause.includes('Motion'));
});

test('processRows handles row with undefined Notes without throwing TypeError', () => {
  const {ctx} = createEnv();
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  const row = {
    Id: 2, Archived: 'No', Emailed: 'No', Name: 'Event 2', Monitor: 'Front', Cause: 'Motion',
    Notes: undefined, Frames: 10, AlarmFrames: 2, MaxScore: 10, DiskSpace: '1MB', Storage: 'Default',
    Length: 5,
  };
  const result = ctx.processRows([row]);
  assert.strictEqual(result.length, 1);
  assert.ok(row.Cause.includes('Motion'));
});

test('processRows with empty Notes does not append empty muted div to Cause', () => {
  const {ctx} = createEnv();
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  const row = {
    Id: 3, Archived: 'No', Emailed: 'No', Name: 'Event 3', Monitor: 'Front', Cause: 'Motion',
    Notes: '', Frames: 10, AlarmFrames: 2, MaxScore: 10, DiskSpace: '1MB', Storage: 'Default',
    Length: 5,
  };
  ctx.processRows([row]);
  assert.strictEqual(row.Cause.indexOf('<div class="small text-muted"></div>'), -1,
      'Cause should not contain empty div when Notes is empty');
});

test('processRows returns empty array when given null or undefined rows', () => {
  const {ctx} = createEnv();
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  const resNull = ctx.processRows(null);
  assert.ok(Array.isArray(resNull), 'processRows(null) should return an array');
  assert.strictEqual(resNull.length, 0);

  const resUndef = ctx.processRows(undefined);
  assert.ok(Array.isArray(resUndef), 'processRows(undefined) should return an array');
  assert.strictEqual(resUndef.length, 0);
});

test('Monitor column link uses canView.Events permission instead of canEdit.Monitors', () => {
  const {ctx} = createEnv({canViewEvents: true, canEditMonitors: false});
  vm.runInContext(extract('processRows') + '; this.processRows = processRows;', ctx);
  const row = {
    Id: 4, Archived: 'No', Emailed: 'No', Name: 'Event 4', Monitor: 'Driveway', Cause: 'Motion',
    Notes: '', Frames: 10, AlarmFrames: 2, MaxScore: 10, DiskSpace: '1MB', Storage: 'Default',
    Length: 5,
  };
  ctx.processRows([row]);
  assert.ok(row.Monitor.includes('<a href="?view=event&amp;eid=4">Driveway</a>'),
      'Monitor should be a link for user with canView.Events even if canEdit.Monitors is false');
});

test('totalDiskSpaceFormatter handles null or missing footerData safely', () => {
  const {ctx} = createEnv();
  vm.runInContext('var footerData = null;\n' + extract('totalDiskSpaceFormatter') + '; this.totalDiskSpaceFormatter = totalDiskSpaceFormatter;', ctx);
  const res = ctx.totalDiskSpaceFormatter([]);
  assert.strictEqual(res, '');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
