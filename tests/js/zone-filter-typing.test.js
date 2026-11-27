'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/zone.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

console.log('filter size boxes');
test('limitFilter is applied on change, not on each keystroke', () => {
  const bound = {};
  const field = (name) => ({name: name, disabled: false});
  const elements = {};
  ['newZone[FilterX]', 'newZone[FilterY]'].forEach((n) => elements[n] = field(n));
  const names = new Proxy(elements, {
    get: (t, p) => (p in t ? t[p] : field(String(p))),
  });
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, Array, Object,
    $j: chain,
    window: {addEventListener() {}},
    document: {
      zoneForm: {elements: names, presetSelector: {}},
      getElementById: () => null,
      querySelectorAll: () => [],
    },
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.window.limitFilter = ctx.limitFilter;
  ctx.window.presetSelectorBlur = ctx.presetSelectorBlur;
  ctx.window.applyCheckMethod = ctx.applyCheckMethod;
  ctx.window.limitRangeToUnsignedByte = ctx.limitRangeToUnsignedByte;
  ctx.window.limitArea = ctx.limitArea;
  ctx.window.updatePercentFromPixel = ctx.updatePercentFromPixel;
  try {
    ctx.initPage();
  } catch {
    // The rest of the page set up needs a real DOM; the boxes are wired first.
  }
  Object.keys(elements).forEach((n) => bound[n] = elements[n]);
  ['newZone[FilterX]', 'newZone[FilterY]'].forEach((n) => {
    assert.strictEqual(typeof bound[n].oninput, 'undefined', n + ' oninput');
    assert.strictEqual(typeof bound[n].onchange, 'function', n + ' onchange');
  });
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
