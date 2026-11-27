'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.argv[2] || path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const start = src.indexOf('function manageShutdownBtns');
const end = src.indexOf('\n}\n', start) + 3;

function run(cmd, response) {
  const els = {};
  const el = (id) => els[id] || (els[id] = {
    html: '', disabled: null, invisible: true,
    removeClass() {
      this.invisible = false;
    },
    html_(h) {
      this.html = h;
    },
    prop(n, v) {
      this.disabled = v;
    },
    is() {
      return true;
    },
  });
  const $j = (sel) => {
    const e = el(sel);
    e.html = e.html || '';
    const api = {
      removeClass: () => e.removeClass(),
      prop: (n, v) => e.prop(n, v),
      is: () => true,
      html: (h) => {
        e.html = h;
      },
    };
    return api;
  };
  $j.getJSON = () => {
    const p = {done: (fn) => {
      fn(response);
      return p;
    }, fail: () => p};
    return p;
  };
  const ctx = {$j, thisUrl: 'index.php', cancelString: 'Cancel', logAjaxFail: () => {}, console};
  vm.createContext(ctx);
  vm.runInContext(src.slice(src.indexOf('function escapeHTML'), src.indexOf('function logAjaxFail')), ctx);
  vm.runInContext(src.slice(start, end), ctx);
  ctx.manageShutdownBtns({getAttribute: () => cmd});
  return els;
}

// The ajax handler answers {result:'Error', message} when the request is refused
let els = run('shutdown', {result: 'Error', message: 'Need System permissions to shutdown server'});
assert.ok(!/Success/.test(els['#respText'].html), 'refused request reported as success: ' + els['#respText'].html);
assert.ok(/Need System permissions/.test(els['#respText'].html), 'refusal message not shown: ' + els['#respText'].html);
console.log('  ok refused request is shown as an error');

// A cancelled shutdown leaves nothing to cancel
els = run('cancel', {result: 'Ok', rc: 0, output: []});
assert.strictEqual(els['#cancelBtn'].disabled, true, 'Cancel stays enabled after a successful cancel');
console.log('  ok cancel button disabled after cancelling');

// A failed command returns its output as an array of lines
els = run('shutdown', {result: 'Ok', rc: 1, output: ['sudo: a password is required', 'try <b>again</b>']});
const failHtml = els['#respText'].html;
assert.strictEqual(typeof failHtml, 'string', 'output lines passed to html() as ' + typeof failHtml);
assert.ok(/required<br\/>try/.test(failHtml), 'output lines not separated: ' + failHtml);
assert.ok(!/<b>/.test(failHtml), 'command output inserted as raw HTML: ' + failHtml);
console.log('  ok failed command output is shown as escaped lines');
