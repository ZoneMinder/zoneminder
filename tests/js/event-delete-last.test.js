'use strict';

// Deleting the last event of the list called streamNext(), which only put
// "No more events" over the event that had just been deleted. The view should
// go to the previous event instead.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.EVENT_JS || process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');

function fn(name) {
  const m = src.match(new RegExp('\\nfunction ' + name + '\\([^)]*\\) \\{[\\s\\S]*?\\n\\}\\n'));
  assert.ok(m, name + ' not found in event.js');
  return m[0];
}

function run(prevEventId, nextEventId, shift) {
  const seen = {calls: []};
  let confirm;
  const $j = () => ({modal() {}});
  $j.getJSON = () => {
    const p = {done(cb) {
      cb({result: 'Ok'}); return p;
    }, fail() {
      return p;
    }};
    return p;
  };
  const sandbox = {
    $j, thisUrl: '?', eventData: {Id: 5}, canEdit: {Events: true}, vid: {},
    prevEventId, nextEventId, enoperm() {}, logAjaxFail() {}, alert() {},
    pauseClicked() {}, streamReq() {}, restartZmsStream() {},
    console: {error() {}},
    streamNext: () => seen.calls.push('next'),
    streamPrev: () => seen.calls.push('prev'),
    document: {getElementById: (id) => ({addEventListener: (ev, cb) => {
      if (id == 'delConfirmBtn') confirm = cb;
    }})},
  };
  vm.createContext(sandbox);
  let code = fn('deleteAnswered') + fn('manageDelConfirmModalBtns');
  if (src.includes('function eventDeleted(')) code += fn('eventDeleted');
  vm.runInContext(code + '\nmanageDelConfirmModalBtns();', sandbox);
  confirm({preventDefault() {}});
  return seen.calls;
}

assert.deepStrictEqual(run(3, 7), ['next'], 'a next event is shown');
assert.deepStrictEqual(run(3, 0), ['prev'], 'the last event goes to the previous one');
assert.deepStrictEqual(run(0, 0), ['next'], 'the only event keeps the no-more-events message');
console.log('ok - deleting the last event shows the previous one');
