'use strict';

// The event view's Archive and Unarchive buttons reloaded the page whatever
// the reply. ajax/events.php refuses with HTTP 200 and result 'Error', so a
// refused change just reloaded with nothing changed and no message.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.EVENT_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');

function handler(name) {
  const m = src.match(new RegExp('function ' + name + '\\(evt\\) \\{[\\s\\S]*?\\n {2}\\}'));
  assert.ok(m, name + ' not found in event.js');
  return m[0];
}

function click(name, reply) {
  const seen = {alerts: [], reloads: 0};
  const $j = () => ({});
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
  const sandbox = {
    $j, thisUrl: '?', eventData: {Id: 5}, canEdit: {Events: true},
    enoperm() {}, logAjaxFail() {},
    alert: (m) => seen.alerts.push(m),
    window: {location: {reload: () => seen.reloads++}},
  };
  vm.createContext(sandbox);
  vm.runInContext('const h = ' + handler(name) + ';\nh({preventDefault() {}});', sandbox);
  return seen;
}

for (const name of ['onArchiveClick', 'onUnarchiveClick']) {
  let r = click(name, {result: 'Error', message: 'Not allowed'});
  assert.strictEqual(r.reloads, 0, name + ': a refused change does not reload');
  assert.deepStrictEqual(r.alerts, ['Not allowed'], name + ': and says why');
  r = click(name, {result: 'Ok'});
  assert.strictEqual(r.reloads, 1, name + ': a successful change reloads');
  assert.strictEqual(r.alerts.length, 0, name + ': without a message');
}
console.log('ok - archive and unarchive report a refused change');
