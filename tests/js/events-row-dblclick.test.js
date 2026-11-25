'use strict';

// Double-clicking anywhere on an events row should open that event, the same
// target as the small Id/Name link, while a single click still selects the row.
// A double-click on a link, button or checkbox inside the row must be left to
// that control instead of navigating a second time.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/events.js'), 'utf8');

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

// Runs initPage() and returns the .on() handlers it registered plus a record of
// window.location.assign() calls. Rows are rendered by processRows(), so Id is
// the Id anchor HTML by the time a row is double-clicked.
function createEnv(options = {}) {
  const handlers = {};
  const navigations = [];
  const table = {
    bootstrapTable() {
      return table;
    },
    on(events, fn) {
      events.split(/\s+/).forEach((name) => {
        if (name) handlers[name] = fn;
      });
      return table;
    },
    find() {
      return {addClass() {}};
    },
    show() {},
  };
  const any = new Proxy(function() {}, {
    get(target, prop) {
      if (prop === 'length') return 0;
      if (prop === Symbol.toPrimitive) return () => '';
      return any;
    },
    apply() {
      return any;
    },
  });

  // Event targets are plain markers so closest() can report whether the
  // double-click landed on a control inside the row.
  function target(insideControl) {
    return {insideControl};
  }

  const $j = function(sel) {
    if (sel === '#eventTable') return table;
    if (sel && typeof sel === 'object' && 'insideControl' in sel) {
      return {closest: () => ({length: sel.insideControl ? 1 : 0})};
    }
    return any;
  };
  $j.each = () => {};
  $j.map = () => [];

  const ctx = {
    $j,
    document: {referrer: '', getElementById: () => any, addEventListener: () => {}},
    window: {
      location: {
        search: '',
        assign: (url) => navigations.push(url),
      },
      addEventListener: () => {},
    },
    history: {replaceState: () => {}, pushState: () => {}},
    URLSearchParams,
    encodeURIComponent,
    strip_html: (s) => String(s).replace(/<[^>]*>/g, ''),
    console: {log: () => {}},
    icons: {},
    WEB_LIST_THUMBS: true,
    navbar_type: 'left',
    filterQuery: options.filterQuery || '',
    sortQuery: options.sortQuery || '',
    initThumbAnimation: () => {},
    getCookie: () => '',
    setCookie: () => {},
    isMobile: () => true,
    canView: {Events: true},
    canEdit: {Events: true},
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.initPage = initPage;', ctx);
  ctx.initPage();
  return {handlers, navigations, target};
}

console.log('Testing row double-click in web/skins/classic/views/js/events.js:');

test('dbl-click-row opens the row event URL', () => {
  const filterQuery = '&filter[Query][terms][0][val]=1';
  const sortQuery = '&sort_field=Id&sort_asc=1';
  const {handlers, navigations, target} = createEnv({filterQuery, sortQuery});
  assert.ok(handlers['dbl-click-row.bs.table'], 'dbl-click-row handler is registered');

  handlers['dbl-click-row.bs.table'](
      {target: target(false)},
      {Id: '<a href="?view=event&amp;eid=42&amp;page=1">42</a>', Name: 'Event 42'});

  assert.strictEqual(navigations.length, 1);
  assert.strictEqual(
      navigations[0],
      '?view=event&eid=42' + filterQuery + sortQuery + '&page=1',
      'the row link URL and its filter/sort parameters are reused');
});

test('dbl-click-row on a link, button or checkbox does not navigate again', () => {
  const {handlers, navigations, target} = createEnv();
  assert.ok(handlers['dbl-click-row.bs.table'], 'dbl-click-row handler is registered');

  ['link', 'button', 'checkbox'].forEach((control) => {
    handlers['dbl-click-row.bs.table'](
        {target: target(true)},
        {Id: '<a href="?view=event&amp;eid=7&amp;page=1">7</a>', Name: 'Event 7'});
  });

  assert.strictEqual(navigations.length, 0,
      'controls inside the row keep their own behaviour');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
