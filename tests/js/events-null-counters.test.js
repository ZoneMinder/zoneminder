'use strict';

// A just-started event has NULL Frames, AlarmFrames and MaxScore until zmc
// first updates it, and the events list showed "null" in those columns.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/events.js');
const src = fs.readFileSync(file, 'utf8');

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  let depth = 0;
  for (let i = src.indexOf('{', start); ; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
}

function render(frames, alarmFrames, maxScore) {
  const ctx = {
    $j: {each: (arr, fn) => arr.forEach((v, i) => fn(i, v))},
    canEdit: {}, yesString: 'Yes', archivedString: '', emailedString: '',
    filterQuery: '', sortQuery: '', Date, String,
  };
  vm.createContext(ctx);
  vm.runInContext(extract('processRows') + ';this.processRows = processRows', ctx);
  const row = {
    Id: 1, Archived: 'No', Emailed: 'No', Name: 'n', Monitor: 'm', Cause: 'c', Notes: '',
    Frames: frames, AlarmFrames: alarmFrames, MaxScore: maxScore,
    DiskSpace: '', Storage: '', Length: 0, Latitude: null, Longitude: null,
  };
  try {
    ctx.processRows([row]);
  } catch (e) {
    if (row.MaxScore === maxScore) throw e;
  }
  return row;
}

const fresh = render(null, null, null);
assert.ok(!/null/.test(fresh.Frames), 'Frames shows null: ' + fresh.Frames);
assert.ok(!/null/.test(fresh.AlarmFrames), 'AlarmFrames shows null: ' + fresh.AlarmFrames);
assert.ok(!/null/.test(fresh.MaxScore), 'MaxScore shows null: ' + fresh.MaxScore);
assert.ok(/>0</.test(fresh.Frames), 'Frames shows 0');

const done = render(25, 3, 40);
assert.ok(/>25</.test(done.Frames) && />3</.test(done.AlarmFrames) && />40</.test(done.MaxScore),
    'real counts are shown as they are');
console.log('ok - events list shows 0 for a new event\'s frame counts');
