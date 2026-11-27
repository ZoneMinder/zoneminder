'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const scriptPath = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/cycle.js');
const src = fs.readFileSync(scriptPath, 'utf8');

function makeSessionStorage() {
  const store = {};
  return {
    getItem(k) {
      return Object.prototype.hasOwnProperty.call(store, k) ? store[k] : null;
    },
    setItem(k, v) {
      store[k] = String(v);
    },
    removeItem(k) {
      delete store[k];
    },
  };
}

function load(options) {
  const opts = options || {};
  const record = [];
  const urls = [];
  const timers = {setIntervalCalls: 0, nextId: 1, cleared: []};

  const $j = (sel) => {
    const el = {
      val: () => 'auto',
      prop(name, value) {
        record.push({sel: String(sel), name, value});
        return el;
      },
      css() {
        return el;
      },
      width() {
        return el;
      },
      height() {
        return el;
      },
      ready() {
        return el;
      },
    };
    return el;
  };

  const sandbox = {
    $j,
    document: {},
    monitorData: [{id: 1}, {id: 2}, {id: 3}],
    monIdx: '0',
    mode: 'single',
    cycleRefreshTimeout: 1000,
    nextMid: '2',
    console,
    setInterval(fn, ms) {
      timers.setIntervalCalls++;
      return timers.nextId++;
    },
    clearInterval(id) {
      timers.cleared.push(id);
    },
    setCookie() {},
    SCALE_BASE: 100,
    zmAuth: {appendTo: (u) => u},
    window: {location: {replace(u) {
      urls.push(u);
    }}},
    MonitorStream: function() {
      this.setScale = () => {};
      this.start = () => {};
      this.setStreamScale = () => {};
    },
  };
  if (opts.sessionStorage !== undefined) {
    sandbox.sessionStorage = opts.sessionStorage;
  }

  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, record, urls, timers};
}

function hasProp(record, sel, name, value) {
  return record.some((c) => c.sel === sel && c.name === name && c.value === value);
}

const storage = makeSessionStorage();

{
  const first = load({sessionStorage: storage});
  first.sandbox.initCycle();
  assert.strictEqual(first.timers.setIntervalCalls, 1,
      'initCycle should start one interval on the first load');
  first.sandbox.cyclePause();
  first.sandbox.cycleNext();
  assert.strictEqual(first.urls.length, 1, 'cycleNext should request a reload');
  assert.ok(first.urls[0].includes('mid=2'), first.urls[0]);
  console.log('ok first load starts the interval and pause requests a reload');
}

{
  const second = load({sessionStorage: storage});
  second.sandbox.initCycle();
  assert.strictEqual(second.timers.setIntervalCalls, 0,
      'a pause should survive a page reload');
  assert.ok(hasProp(second.record, '#playBtn', 'disabled', false),
      'playBtn should be enabled after reloading while paused');
  assert.ok(hasProp(second.record, '#pauseBtn', 'disabled', true),
      'pauseBtn should be disabled after reloading while paused');
  console.log('ok paused state survives a reload and updates the buttons');

  second.sandbox.cycleStart();
  assert.strictEqual(second.timers.setIntervalCalls, 1,
      'cycleStart should start exactly one interval');
}

{
  const third = load({sessionStorage: storage});
  third.sandbox.initCycle();
  assert.strictEqual(third.timers.setIntervalCalls, 1,
      'playing should clear the pause across a reload');
  console.log('ok play clears the pause across a reload');
}

{
  const bare = load({});
  assert.doesNotThrow(() => bare.sandbox.initCycle());
  assert.strictEqual(bare.timers.setIntervalCalls, 1,
      'initCycle should start the interval when sessionStorage is unavailable');
  console.log('ok initCycle starts the interval without sessionStorage');
}
