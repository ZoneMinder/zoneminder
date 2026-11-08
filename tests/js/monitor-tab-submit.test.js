'use strict';

// Tests that resubmitting the monitor form for a field that changes the form's
// shape comes back on the tab you were working in.
//
// Two files have to agree for that to happen, so both are loaded into one
// sandbox, as a page has them:
//
//   monitor.js  keeps contentForm's hidden "tab" field in step with the pill
//               that is showing.
//   skin.js     submitTab() resubmits the form without saving, and the view
//               reads that field to decide which tab to render.
//
// submitTab() used to do form.attr('tab', tab), which set an attribute named
// "tab" on the <form> element and did nothing: an attribute on a form is not
// a submitted value. The interesting part is that writing it correctly would
// have been worse than leaving it broken -- the value it was being handed,
// data-tab-name, is the tab the page loaded on, so assigning it would undo
// the tracking and send anyone who had switched tabs back to where they
// started. So what is checked here is that submitTab leaves the field alone.
//
// Run: node tests/js/monitor-tab-submit.test.js

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
const skinSrc = fs.readFileSync(path.join(root, 'web/skins/classic/js/skin.js'), 'utf8');
const monitorSrc = fs.readFileSync(
    path.join(root, 'web/skins/classic/views/js/monitor.js'), 'utf8');

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

/**
 * Load skin.js and monitor.js into one sandbox, with a form and a pill list
 * that record what is done to them.
 * @param {string} loadedTab the tab php rendered the page on, which is what
 *     the hidden field starts out holding.
 * @return {!Object} the sandbox globals and the recordings
 */
function load(loadedTab) {
  // contentForm, as both files reach it: monitor.js by getElementById and
  // skin.js through $j.
  const form = {
    tab: {value: loadedTab},
    attributes: {},
    submits: 0,
    setAttribute: function(n, v) {
      this.attributes[n] = v;
    },
    querySelectorAll: () => [],
    elements: {},
  };
  const formWrapper = {
    attr: function(n, v) {
      form.attributes[n] = v;
      return this;
    },
    // jQuery's submit() triggers the form's submit handlers; the element's own
    // submit() skips them, and contentForm's handler validates the form and
    // blanks the password fields.
    submit: function() {
      form.submits++;
      return this;
    },
    on: () => formWrapper,
    serialize: () => '',
    length: 1,
  };

  const pillBindings = [];
  const noop = {
    on: function() {
      return this;
    },
    submit: function() {
      return this;
    },
    attr: function() {
      return this;
    },
    hide: function() {
      return this;
    },
    show: function() {
      return this;
    },
    click: function() {
      return this;
    },
    each: function() {
      return this;
    },
    find: function() {
      return this;
    },
    css: function() {
      return this;
    },
    prop: function() {
      return this;
    },
    addClass: function() {
      return this;
    },
    removeClass: function() {
      return this;
    },
    append: function() {
      return this;
    },
    replaceWith: function() {
      return this;
    },
    modal: function() {
      return this;
    },
    sortable: function() {
      return this;
    },
    bootstrapTable: function() {
      return this;
    },
    ready: function() {
      return this;
    },
    val: () => '',
    data: () => undefined,
    serialize: () => '',
    length: 0,
  };

  function $j(selector) {
    if (selector === '#contentForm') return formWrapper;
    if (typeof selector === 'string' && selector.indexOf('[data-toggle="pill"]') !== -1) {
      return {
        on: function(event, handler) {
          pillBindings.push({selector: selector, event: event, handler: handler});
          return this;
        },
      };
    }
    return noop;
  }
  $j.ajaxSetup = () => {};
  $j.getJSON = () => ({done: function() {
    return this;
  }, fail: function() {
    return this;
  }});
  $j.noConflict = () => $j;
  $j.extend = Object.assign;
  $j.fn = {};

  const globals = {
    console: {log: () => {}, error: () => {}, trace: () => {}, warn: () => {}},
    JSON: JSON, Object: Object, Array: Array, Math: Math, String: String,
    Number: Number, Date: Date, RegExp: RegExp, Error: Error, Promise: Promise,
    URLSearchParams: URLSearchParams,
    setTimeout: () => 0, clearTimeout: () => {},
    setInterval: () => 0, clearInterval: () => {},
    $j: $j, jQuery: $j,
    currentView: 'monitor',
    AJAX_TIMEOUT: 1000,
    addEventListener: () => {},
    location: {search: '?view=monitor&mid=24', href: 'http://host/zm/index.php?view=monitor&mid=24'},
    history: {replaceState: () => {}, pushState: () => {}},
    localStorage: {getItem: () => null, setItem: () => {}},
    navigator: {userAgent: 'node'},
    screen: {width: 1, height: 1},
    luxon: {DateTime: {fromFormat: () => ({isValid: false}), now: () => ({toFormat: () => ''})}},
    document: {
      getElementById: function(id) {
        return (id === 'contentForm') ? form : null;
      },
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({style: {}, setAttribute: () => {}, appendChild: () => {}}),
      body: {appendChild: () => {}},
      documentElement: {},
      cookie: '',
    },
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(skinSrc, sandbox, {filename: 'skin.js'});
  vm.runInContext(monitorSrc, sandbox, {filename: 'monitor.js'});

  assert.strictEqual(typeof globals.submitTab, 'function', 'skin.js did not define submitTab');
  assert.strictEqual(typeof globals.rememberSelectedTab, 'function',
      'monitor.js did not define rememberSelectedTab');
  globals.rememberSelectedTab();
  assert.strictEqual(pillBindings.length, 1, 'expected one pill binding');

  return {
    globals: globals,
    form: form,
    /** @param {string} name the tab to show, as bootstrap would. */
    showTab: function(name) {
      pillBindings[0].handler({target: {getAttribute: () => '#pills-' + name}});
    },
    /**
     * Call submitTab as the Method select's onchange would.
     * @param {?string} tabName a data-tab-name to offer, or null for none.
     */
    changeMethod: function(tabName) {
      const el = {getAttribute: (n) => (n === 'data-tab-name' ? tabName : null)};
      globals.submitTab.call(el, {preventDefault: () => {}});
    },
  };
}

console.log('resubmitting the monitor form keeps the tab you are on');

test('the tab you switched to is the one submitted', () => {
  // The whole point. Load on General, work on Source, change Method: the
  // field that decides where you come back must say source.
  const t = load('general');
  t.showTab('source');
  t.changeMethod('general'); // data-tab-name is still the loaded tab
  assert.strictEqual(t.form.tab.value, 'source');
});

test('and submitTab does not overwrite it with the loaded tab', () => {
  // Writing data-tab-name into the field -- the obvious way to "fix" the old
  // attr call -- would pass the test above only by accident of the two
  // agreeing. Here they disagree and the field must keep tracking.
  const t = load('general');
  t.showTab('actions');
  t.changeMethod('general');
  assert.notStrictEqual(t.form.tab.value, 'general',
      'submitTab put the loaded tab back');
  assert.strictEqual(t.form.tab.value, 'actions');
});

test('without any tab switch the loaded tab still comes back', () => {
  const t = load('source');
  t.changeMethod('source');
  assert.strictEqual(t.form.tab.value, 'source');
});

test('submitTab sets no attribute named tab', () => {
  // The bug: an attribute on a <form> is not a submitted value, so this did
  // nothing at all.
  const t = load('general');
  t.changeMethod('general');
  assert.ok(!('tab' in t.form.attributes),
      'submitTab is still setting a tab attribute on the form');
});

test('it submits to the current url', () => {
  // An empty action posts to the url the page is on, so mid -- and the tab the
  // url now remembers -- travel with it.
  const t = load('general');
  t.changeMethod(null);
  assert.strictEqual(t.form.attributes['action'], '');
});

test('and submits through jQuery, so the form handler runs', () => {
  // contentForm's submit handler validates the form and blanks the password
  // fields. The element's own submit() would skip it.
  const t = load('general');
  t.changeMethod(null);
  assert.strictEqual(t.form.submits, 1);
});

test('it no longer needs data-tab-name at all', () => {
  // Nothing passes it any more, so submitTab must not depend on it.
  const t = load('general');
  t.showTab('buffers');
  t.changeMethod(null);
  assert.strictEqual(t.form.tab.value, 'buffers');
  assert.strictEqual(t.form.submits, 1);
});

console.log('and nothing offers the attribute any more');

test('submitTab does not read data-tab-name', () => {
  assert.ok(!/data-tab-name/.test(
      /function submitTab\([^]*?\n\}/.exec(skinSrc)[0].replace(/\/\/.*$/gm, '')),
  'submitTab still reads data-tab-name');
});

test('and monitor.php does not pass it', () => {
  // An attribute nothing reads is a trap: the next person to touch this would
  // reasonably assume it was doing something.
  const view = fs.readFileSync(
      path.join(root, 'web/skins/classic/views/monitor.php'), 'utf8');
  const live = view.replace(/^\s*#.*$/gm, '');
  assert.ok(!/'data-tab-name'/.test(live), 'monitor.php still passes data-tab-name');
  // The control that uses submitTab is still wired up, though.
  assert.ok(/'data-on-change'=>'submitTab'/.test(live),
      'monitor.php no longer wires the Method select to submitTab');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
