'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js'), 'utf8');

function makeAnchor(href) {
  return {
    href: href,
    prop: function(name, value) {
      if (value === undefined) return this[name];
      this[name] = value;
      return this;
    },
  };
}

function load(anchors) {
  const stub = {
    val: () => '200',
    css: () => stub,
    prop: () => stub,
    off: () => stub,
    on: () => stub,
    width: () => 2000,
    is: () => true,
    toggle: () => stub,
    ready: () => stub,
  };
  const globals = {
    $j: Object.assign(function(selector) {
      if (selector === '#nextLink') return anchors.next;
      if (selector === '#prevLink') return anchors.prev;
      if (selector === '#firstLink') return anchors.first;
      if (selector === '#lastLink') return anchors.last;
      return stub;
    }, {each: (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k]))}),
    SCALE_BASE: 100,
    setCookie: () => {},
    scaleToFit: () => ({width: 1, height: 1, autoScale: 0}),
    endOfResize: () => {},
    document: {referrer: ''},
    window: {},
    console: {log() {}},
  };
  vm.createContext(globals);
  vm.runInContext(src, globals, {filename: 'frame.js'});
  return globals;
}

const base = '?view=frame&eid=7&scale=100&show=capture&fid=';
const anchors = {
  next: makeAnchor(base + '5'),
  prev: makeAnchor(base + '3'),
  first: makeAnchor(base + '1'),
  last: makeAnchor(base + '9'),
};
const ctx = load(anchors);
ctx.changeScale();

assert.strictEqual(anchors.next.href, '?view=frame&eid=7&scale=200&show=capture&fid=5');
assert.strictEqual(anchors.prev.href, '?view=frame&eid=7&scale=200&show=capture&fid=3');
assert.strictEqual(anchors.first.href, '?view=frame&eid=7&scale=200&show=capture&fid=1');
assert.strictEqual(anchors.last.href, '?view=frame&eid=7&scale=200&show=capture&fid=9');
console.log('  ok changing the scale keeps the show parameter in the frame links');
