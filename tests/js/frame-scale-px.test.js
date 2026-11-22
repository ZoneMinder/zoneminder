'use strict';

// changeScale in the frame view must size the image for 'NNNpx' (max width) and
// 'fit_to_width', not multiply the base size by the number in the string.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/frame.js'), 'utf8');

function size(scale, pageWidth) {
  const css = {};
  const stub = {
    val: () => '',
    css: (k, v) => {
      css[k] = v;
      return stub;
    },
    prop: () => stub,
    off: () => stub,
    on: () => stub,
    width: () => pageWidth,
    is: () => true,
    toggle: () => stub,
    ready: () => stub,
  };
  const globals = {
    $j: Object.assign(function(selector) {
      if (selector === '#scale') return Object.assign({}, stub, {val: () => scale});
      if (selector === '#base_width') return Object.assign({}, stub, {val: () => '1920'});
      if (selector === '#base_height') return Object.assign({}, stub, {val: () => '1080'});
      return stub;
    }, {each: () => {}}),
    SCALE_BASE: 100,
    setCookie: () => {},
    // fits the page width, keeping the aspect ratio
    scaleToFit: (w, h) => ({width: pageWidth, height: Math.floor(pageWidth * h / w), autoScale: 0}),
    endOfResize: () => {},
    document: {referrer: ''},
    window: {},
    console: {log() {}},
  };
  vm.createContext(globals);
  vm.runInContext(src, globals, {filename: 'frame.js'});
  globals.changeScale();
  return css;
}

let css = size('480px', 1000);
assert.strictEqual(css.width, '480px');
assert.strictEqual(css.height, '270px');
console.log('  ok 480px caps the width at 480');

css = size('1600px', 1000);
assert.strictEqual(css.width, '1000px');
console.log('  ok a max width wider than the page is held to the page width');

css = size('fit_to_width', 1000);
assert.strictEqual(css.width, '1000px');
assert.strictEqual(css.height, '562px');
console.log('  ok fit_to_width fits the page width');

css = size('50', 1000);
assert.strictEqual(css.width, '960px');
assert.strictEqual(css.height, '540px');
console.log('  ok 50 still halves the size');
