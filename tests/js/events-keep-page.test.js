'use strict';

// Returning to the events list (the event view's back link, the Back button, a
// reload) must keep the page the bootstrap-table cookie remembers. initPage()
// used to call resetSearch unconditionally, and resetSearch sets pageNumber to
// 1 and writes that to the cookie, so the list always came back on page 1.

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

// Runs events.js up to and including initPage() against a table that was
// restored from its cookie with the given options; returns the table calls.
function runInitPage(tableOptions) {
  const calls = [];
  const any = new Proxy(function() {}, {
    get: (t, prop) => {
      if (prop === 'length') return 0;
      if (prop === Symbol.toPrimitive) return () => '';
      if (prop === 'bootstrapTable') {
        return (action) => {
          calls.push(action);
          return action === 'getOptions' ? tableOptions : any;
        };
      }
      return any;
    },
    apply: () => any,
  });
  const ctx = {
    $j: Object.assign(function() {
      return any;
    }, {each: () => {}, map: () => []}),
    document: {referrer: '', getElementById: () => any, addEventListener: () => {}},
    window: {addEventListener: () => {}, location: {}, history: {}},
    history: {replaceState: () => {}, pushState: () => {}},
    URLSearchParams,
    console: {log: () => {}},
    icons: {}, WEB_LIST_THUMBS: true, navbar_type: 'left', filterQuery: '', sortQuery: '',
    initThumbAnimation: () => {}, getCookie: () => 'x', setCookie: () => {}, isMobile: () => true,
    canView: {Events: true}, canEdit: {Events: true},
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.initPage = initPage;', ctx);
  ctx.initPage();
  return calls;
}

test('a table without search text keeps the page restored from its cookie', () => {
  const calls = runInitPage({searchText: '', pageNumber: 3});
  assert.ok(calls.indexOf('resetSearch') < 0, 'resetSearch would send the list back to page 1');
});

test('a search text restored from the cookie is still cleared', () => {
  const calls = runInitPage({searchText: 'person', pageNumber: 3});
  assert.ok(calls.indexOf('resetSearch') >= 0);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
