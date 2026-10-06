'use strict';

// Tests that selecting a tab on the monitor view writes the tab into the url,
// so a reload comes back to it.
//
// The view has always honoured ?tab=<name> on the way in -- php marks that
// pill active and renders its pane shown. What was missing is the other half:
// the pills are switched client side, so the url kept saying whatever it said
// when the page loaded, and a reload dropped you back on General.
//
// The risk in writing it is not whether the tab lands in the url but what
// else the rewrite destroys on the way. The monitor url carries mid, and may
// carry more, and replaceState takes a whole url rather than one parameter.
// So most of what is checked below is what the rewrite leaves alone.
//
// monitor.js is run in a vm sandbox, as the other view script tests do, since
// it exports nothing and is only meant to be loaded by a page.
//
// Run: node tests/js/monitor-tab-url.test.js

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js'), 'utf8');

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
 * Load monitor.js and hand back the tab handler it registers, plus what it
 * wrote to.
 * @param {string} search the page's current query string, with its ?
 * @param {boolean} withForm whether the page has a #contentForm with a tab
 *     field. The monitor view always does, but the handler is asked to cope
 *     without one rather than throw inside a bootstrap event.
 * @return {!Object} the captured handler and the recorded writes
 */
function load(search, withForm) {
  const replaced = [];
  const bound = [];
  const form = withForm ? {tab: {value: 'general'}} : null;

  const globals = {
    console: {log: () => {}, error: () => {}, trace: () => {}},
    URLSearchParams: URLSearchParams,
    Object: Object,
    Array: Array,
    JSON: JSON,
    Math: Math,
    String: String,
    Number: Number,
    setTimeout: () => 0,
    clearTimeout: () => {},
    setInterval: () => 0,
    clearInterval: () => {},
    addEventListener: () => {},
    location: {search: search},
    history: {
      replaceState: function(state, title, url) {
        replaced.push(url);
      },
    },
    document: {
      getElementById: function(id) {
        return (id === 'contentForm') ? form : null;
      },
      querySelectorAll: () => [],
      addEventListener: () => {},
    },
    // Only the one call the handler registration makes is of interest; the
    // rest of monitor.js never runs here.
    $j: function(selector) {
      return {
        on: function(event, handler) {
          bound.push({selector: selector, event: event, handler: handler});
        },
      };
    },
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => (prop === Symbol.unscopables ? undefined : target[prop]),
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'monitor.js'});

  assert.strictEqual(typeof globals.rememberSelectedTab, 'function',
      'monitor.js did not define rememberSelectedTab');
  globals.rememberSelectedTab();
  assert.strictEqual(bound.length, 1, 'expected exactly one binding');

  return {
    binding: bound[0],
    replaced: replaced,
    form: form,
    // Fire the handler as bootstrap would, for a pill pointing at href.
    show: function(href) {
      bound[0].handler({target: {getAttribute: () => href}});
    },
  };
}

console.log('monitor.js remembers the selected tab in the url');

test('it listens for the shown event on the pill list', () => {
  const t = load('?view=monitor&mid=24', true);
  assert.strictEqual(t.binding.event, 'shown.bs.tab');
  // The three things the selector depends on, all of them in monitor.php:
  // the list's id, and the attribute the pill anchors carry.
  assert.strictEqual(t.binding.selector, '#pills-tab a[data-toggle="pill"]');
});

test('showing a tab adds it to the url', () => {
  const t = load('?view=monitor&mid=24', true);
  t.show('#pills-actions');
  assert.deepStrictEqual(t.replaced, ['?view=monitor&mid=24&tab=actions']);
});

test('and the rest of the query survives', () => {
  // The thing most likely to go wrong: rebuilding a url to change one
  // parameter is an easy way to drop the others, and mid is the one the whole
  // page depends on.
  const t = load('?view=monitor&mid=24&newMonitor=1&dup=a&dup=b', true);
  t.show('#pills-source');
  const params = new URLSearchParams(t.replaced[0]);
  assert.strictEqual(params.get('view'), 'monitor');
  assert.strictEqual(params.get('mid'), '24');
  assert.strictEqual(params.get('newMonitor'), '1');
  assert.deepStrictEqual(params.getAll('dup'), ['a', 'b'],
      'a repeated parameter should not be collapsed');
});

test('an existing tab is replaced, not added again', () => {
  const t = load('?view=monitor&mid=24&tab=general', true);
  t.show('#pills-buffers');
  assert.deepStrictEqual(new URLSearchParams(t.replaced[0]).getAll('tab'), ['buffers']);
});

test('showing the tab the url already names rewrites nothing', () => {
  // Bootstrap fires shown.bs.tab for the pill that starts active, so without
  // this the first event of every page load would be a pointless rewrite.
  const t = load('?view=monitor&mid=24&tab=actions', true);
  t.show('#pills-actions');
  assert.deepStrictEqual(t.replaced, []);
});

test('a pill with no pane is ignored', () => {
  // The Zones pill is href="#" and leaves the page instead of showing a pane.
  // It carries no data-toggle either, so it should never arrive -- but
  // "tab=" is a bad url to leave behind if it ever does.
  const t = load('?view=monitor&mid=24', true);
  t.show('#');
  t.show(null);
  t.show('#something-else');
  assert.deepStrictEqual(t.replaced, []);
});

test('the url is written as a bare query, so the path is kept', () => {
  // replaceState resolves a bare query string against the current path. A
  // leading slash or a bare name would move the page off index.php.
  const t = load('?view=monitor&mid=24', true);
  t.show('#pills-misc');
  assert.ok(t.replaced[0].startsWith('?'), 'expected a leading ?, got ' + t.replaced[0]);
});

console.log('and in the form, for a submit that is not a save');

test('the hidden tab field follows the url', () => {
  // Changing Type or Method resubmits the form to redraw the fields that type
  // needs. The hidden tab field is what tells the view where to come back to,
  // and php writes it once with the tab the page loaded on.
  const t = load('?view=monitor&mid=24', true);
  t.show('#pills-recording');
  assert.strictEqual(t.form.tab.value, 'recording');
});

test('an ignored pill leaves it alone', () => {
  const t = load('?view=monitor&mid=24&tab=viewing', true);
  t.show('#');
  assert.strictEqual(t.form.tab.value, 'general');
});

test('and a page without the form does not throw', () => {
  const t = load('?view=monitor&mid=24', false);
  t.show('#pills-misc');
  assert.deepStrictEqual(t.replaced, ['?view=monitor&mid=24&tab=misc']);
});

console.log('the view provides what the handler reaches for');

// The handler finds the pills by a selector and reads the tab name out of an
// href. Both are shapes in monitor.php, and if either changes the handler
// stops firing without erroring -- the tabs keep working, the url just goes
// back to being wrong. So the markup is checked against the selector the
// handler actually registered rather than against a copy of it.
const view = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/monitor.php'), 'utf8');

test('the pill list has the id the selector uses', () => {
  const selector = load('?view=monitor&mid=1', true).binding.selector;
  const id = /^#([\w-]+)\s/.exec(selector);
  assert.ok(id, 'could not read an id out of ' + selector);
  assert.ok(new RegExp('<ul[^>]*id="' + id[1] + '"').test(view),
      'monitor.php has no <ul id="' + id[1] + '">');
});

test('and the pills carry the attribute it filters on', () => {
  const selector = load('?view=monitor&mid=1', true).binding.selector;
  const attr = /a\[([\w-]+)="([^"]+)"\]/.exec(selector);
  assert.ok(attr, 'could not read an attribute out of ' + selector);
  assert.ok(new RegExp("'" + attr[1] + '="' + attr[2] + '" ').test(view),
      'monitor.php does not give the pills ' + attr[1] + '="' + attr[2] + '"');
});

test('the panes are named the way the handler parses them', () => {
  // The handler takes the tab name from the href by dropping a #pills- prefix,
  // so the anchors must point at #pills-<name> and the panes must be
  // id="pills-<name>" for the two halves to describe the same tab.
  assert.ok(/href="#pills-' \. \$name/.test(view),
      'the pill anchors do not point at #pills-<name>');
  assert.ok(/id="pills-'\.\$name\.'"/.test(view),
      'the panes are not id="pills-<name>"');
});

test('and php honours the tab the url carries', () => {
  // The whole point: without this the url would be rewritten and the reload
  // would ignore it. The name is checked against the tab list rather than
  // trusted, so an unknown tab falls back instead of rendering nothing.
  assert.ok(/isset\(\$_REQUEST\['tab'\]\)\s*and\s*isset\(\$tabs\[\$_REQUEST\['tab'\]\]\)/.test(view),
      'monitor.php does not validate ?tab= against its tab list');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
