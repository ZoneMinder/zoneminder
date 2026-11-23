'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.resolve(__dirname, process.argv[2] || '../../web/skins/classic/views/js/frame.js'), 'utf8');

function makeAnchor(href) {
  return {
    href: href === undefined ? '' : href,
    hrefSet: false,
    prop: function(name, value) {
      if (value === undefined) {
        const current = this[name];
        return current === undefined ? '' : current;
      }
      if (name === 'href') this.hrefSet = true;
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
  prev: makeAnchor(),
  first: makeAnchor(),
  last: makeAnchor(base + '9'),
};
const ctx = load(anchors);
ctx.changeScale();

assert.strictEqual(anchors.next.href, '?view=frame&eid=7&scale=200&show=capture&fid=5');
assert.strictEqual(anchors.last.href, '?view=frame&eid=7&scale=200&show=capture&fid=9');
// Setting an href, even an empty one, turns a disabled link into a live link to this page
assert.strictEqual(anchors.prev.hrefSet, false);
assert.strictEqual(anchors.first.hrefSet, false);
console.log('  ok disabled frame links stay without href after a scale change');
