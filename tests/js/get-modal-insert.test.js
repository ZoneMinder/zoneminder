'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

function extract(startMarker, endMarker) {
  const start = src.indexOf(startMarker);
  assert.ok(start >= 0, 'missing ' + startMarker);
  const end = src.indexOf(endMarker, start);
  return src.slice(start, end + endMarker.length);
}

// A modal's markup carries id="<name>Modal", so getModal() must look for that id
// when deciding whether to replace an earlier copy.
function run(html) {
  const dom = [];
  const find = (id) => dom.filter((e) => e.id === id);
  const $j = function(sel) {
    const els = sel === 'body' ? [] : find(sel.slice(1));
    return {
      length: els.length,
      replaceWith: (h) => els.forEach((e) => {
        e.html = h;
      }),
      append: (h) => dom.push({id: h.id, html: h}),
      modal: () => {},
    };
  };
  $j.getJSON = () => {
    const p = {done: (fn) => {
      fn({html: html});
      return p;
    }, fail: () => p};
    return p;
  };
  const ctx = {$j, thisUrl: 'index.php', manageModalBtns: () => {}, logAjaxFail: () => {}, console};
  vm.createContext(ctx);
  vm.runInContext(extract('function insertModalHtml', '\n  }\n'), ctx);
  vm.runInContext(extract('function getModal', '\n}\n'), ctx);
  ctx.getModal('filterdebug', 'a=1');
  ctx.getModal('filterdebug', 'a=2');
  return dom;
}

const dom = run({id: 'filterdebugModal', body: 'x'});
assert.strictEqual(dom.length, 1, 'second getModal appended a duplicate modal: ' + dom.length);
console.log('  ok getModal replaces the existing modal');
