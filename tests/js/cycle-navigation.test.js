'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(path.join(__dirname, '../../web/skins/classic/views/js/cycle.js'), 'utf8');

function load(monitorData, monIdx) {
  const urls = [];
  const $j = () => ({
    val: () => 'auto', prop() {}, css() {}, width() {}, height() {}, ready() {},
  });
  const sandbox = {
    $j, document: {}, monitorData, monIdx: String(monIdx), mode: 'single', cycleRefreshTimeout: 1000,
    nextMid: '', console, setInterval: () => 1, clearInterval() {},
    setCookie() {}, SCALE_BASE: 100,
    zmAuth: {appendTo: (u) => u},
    window: {location: {replace(u) {
      urls.push(u);
    }}},
    MonitorStream: function() {},
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, urls};
}

const mons = [{id: 1}, {id: 2}, {id: 3}];

// No monitors: resizing the frame must not throw
{
  const {sandbox} = load([], 0);
  sandbox.initCycle();
  assert.doesNotThrow(() => sandbox.changeSize());
  assert.doesNotThrow(() => sandbox.changeScale());
  console.log('ok resizing with no monitors does not throw');
}

// Clicking Next twice before the page has unloaded must not skip a monitor
{
  const {sandbox, urls} = load(mons, 0);
  sandbox.cycleNext();
  sandbox.cycleNext();
  assert.strictEqual(urls[0], urls[1]);
  assert.ok(urls[1].includes('mid=2'), urls[1]);
  console.log('ok double next does not skip');
}

// Same for Prev, including wrap-around
{
  const {sandbox, urls} = load(mons, 0);
  sandbox.cyclePrev();
  sandbox.cyclePrev();
  assert.strictEqual(urls[0], urls[1]);
  assert.ok(urls[1].includes('mid=3'), urls[1]);
  console.log('ok double prev does not skip');
}

// Wrap-around from the last monitor
{
  const {sandbox, urls} = load(mons, 2);
  sandbox.cycleNext();
  assert.ok(urls[0].includes('mid=1'), urls[0]);
  console.log('ok next wraps to first');
}
