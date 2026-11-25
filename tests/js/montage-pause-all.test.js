'use strict';

// The montage toolbar toggle must pause every stream on the first click and
// resume every stream on the second, updating its icon and title to match.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/montage.js'), 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function makeButton() {
  const attrs = {
    "title": 'Pause All',
    'data-title-pause-all': 'Pause All',
    'data-title-play-all': 'Resume All',
  };
  const icon = {textContent: 'pause'};
  let clickFn = null;
  const btn = {
    addEventListener(type, fn) {
      if (type === 'click') clickFn = fn;
    },
    getAttribute(name) {
      return attrs[name];
    },
    setAttribute(name, value) {
      attrs[name] = value;
    },
    querySelector(selector) {
      return selector === '.material-icons' ? icon : null;
    },
  };
  return {
    attrs,
    btn,
    icon,
    click: () => clickFn({preventDefault() {}}),
  };
}

function load() {
  const {attrs, btn, icon, click} = makeButton();
  const sandbox = {
    console: {log() {}, warn() {}, error() {}, debug() {}},
    $j: () => ({on() {}}),
    ResizeObserver: class {
      observe() {}
    },
    document: {
      getElementById: (id) => (id === 'pauseAllBtn' ? btn : {addEventListener() {}}),
      querySelectorAll: () => [],
      addEventListener() {},
    },
    stringToNumber: (s) => parseInt(String(s).replace(/\D/g, '')),
  };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, attrs, icon, click};
}

function stream() {
  return {
    paused: 0,
    played: 0,
    pause() {
      this.paused++;
    },
    play() {
      this.played++;
    },
  };
}

test('first click pauses every monitor stream and flips the icon', () => {
  const {sandbox, attrs, icon, click} = load();
  const a = stream();
  const b = stream();
  Object.assign(sandbox, {a, b});
  vm.runInContext('monitors.push(a, b)', sandbox);
  sandbox.setupPauseAllMonitors();
  click();
  assert.strictEqual(a.paused, 1);
  assert.strictEqual(b.paused, 1);
  assert.strictEqual(a.played, 0);
  assert.strictEqual(b.played, 0);
  assert.strictEqual(icon.textContent, 'play_arrow');
  assert.strictEqual(attrs.title, 'Resume All');
});

test('second click resumes every monitor stream and restores the icon', () => {
  const {sandbox, attrs, icon, click} = load();
  const a = stream();
  const b = stream();
  Object.assign(sandbox, {a, b});
  vm.runInContext('monitors.push(a, b)', sandbox);
  sandbox.setupPauseAllMonitors();
  click();
  click();
  assert.strictEqual(a.paused, 1);
  assert.strictEqual(b.paused, 1);
  assert.strictEqual(a.played, 1);
  assert.strictEqual(b.played, 1);
  assert.strictEqual(icon.textContent, 'pause');
  assert.strictEqual(attrs.title, 'Pause All');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
