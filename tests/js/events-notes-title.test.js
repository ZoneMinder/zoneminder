'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.env.EVENTS_JS ||
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
  let depth = 0;
  for (let i = src.indexOf('{', start); ; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
}

function cause(notes) {
  const ctx = {
    $j: {each: (arr, fn) => arr.forEach((v, i) => fn(i, v))},
    canEdit: {Events: true}, yesString: 'Yes', archivedString: '', emailedString: '',
    filterQuery: '', sortQuery: '', ZM_DATETIME_FORMAT_PATTERN: '', WEB_LIST_THUMBS: false,
    Date, String, console: {log() {}},
  };
  vm.createContext(ctx);
  vm.runInContext(extract('processRows') + ';this.processRows = processRows', ctx);
  const row = {
    Id: 1, Archived: 'No', Emailed: 'No', Name: 'n', Monitor: 'm', Cause: 'c', Notes: notes,
    Frames: 1, AlarmFrames: 0, MaxScore: 0, DiskSpace: '', Storage: '', Length: 5,
    Latitude: null, Longitude: null,
  };
  ctx.processRows([row]);
  return row.Cause;
}

function title(causeHtml) {
  const m = /title="([^"]*)"/.exec(causeHtml);
  assert.ok(m, 'Cause should have a title attribute');
  return m[1];
}

test('a note with a line break gets a real newline in the title', () => {
  const html = cause('line one<br />line two');
  assert.strictEqual(title(html), 'line one\nline two');
  assert.strictEqual(title(html).indexOf('<br'), -1);
  assert.ok(html.indexOf('line one<br />line two') >= 0);
});

test('an empty note gets an empty title', () => {
  assert.strictEqual(title(cause('')), '');
});

test('a note without a line break is unchanged in the title', () => {
  assert.strictEqual(title(cause('line one line two')), 'line one line two');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
