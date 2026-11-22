'use strict';

// Going Back/Forward on the events list restores the filter form from the URL.
// buildFilterQuery() sends a multiple select (Monitor, Group, Tags, Notes...)
// as one comma separated value, so restoring it with el.val('1,2') matched no
// option, cleared the selection and the list showed events for every monitor.

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

function makeSelect(name, values, multiple) {
  return {
    tag: 'select', name, multiple, triggered: [],
    options: values.map((v) => ({value: v, selected: false})),
  };
}
function makeInput(name, value) {
  return {tag: 'input', name, value, triggered: []};
}

// Mirrors jQuery's .val() for inputs and (multiple) selects.
function wrap(el) {
  return {
    attr: (k) => el[k],
    prop: (k) => el[k],
    trigger: (e) => {
      el.triggered.push(e);
      return wrap(el);
    },
    val: function(v) {
      if (arguments.length === 0) {
        if (el.tag === 'input') return el.value;
        const sel = el.options.filter((o) => o.selected).map((o) => o.value);
        return el.multiple ? sel : (sel[0] === undefined ? null : sel[0]);
      }
      if (el.tag === 'input') {
        el.value = v;
      } else {
        const vals = Array.isArray(v) ? v.map(String) : [String(v)];
        el.options.forEach((o) => {
          o.selected = vals.indexOf(o.value) >= 0;
        });
      }
      return wrap(el);
    },
  };
}

// Runs initPage(), then fires popstate with the given location.search.
function runPopstate(fields, search) {
  let popstate = null;
  const calls = [];
  const any = new Proxy(function() {}, {
    get: (t, prop) => {
      if (prop === 'length') return 0;
      if (prop === Symbol.toPrimitive) return () => '';
      if (prop === 'bootstrapTable') {
        return (action) => {
          calls.push(action);
          return action === 'getOptions' ? {searchText: ''} : any;
        };
      }
      return any;
    },
    apply: () => any,
  });
  const $j = function(sel) {
    if (sel === '#fieldsTable input, #fieldsTable select') {
      return {each: (fn) => fields.forEach((el) => fn.call(el))};
    }
    if (sel && typeof sel === 'object' && sel.tag) return wrap(sel);
    return any;
  };
  Object.assign($j, {each: () => {}, map: () => []});
  const ctx = {
    $j,
    document: {referrer: '', getElementById: () => any, addEventListener: () => {}},
    window: {
      addEventListener: (ev, fn) => {
        if (ev === 'popstate') popstate = fn;
      },
      location: {search},
    },
    history: {replaceState: () => {}, pushState: () => {}},
    URLSearchParams,
    encodeURIComponent,
    console: {log: () => {}},
    icons: {}, WEB_LIST_THUMBS: true, navbar_type: 'left', filterQuery: '', sortQuery: '',
    initThumbAnimation: () => {}, getCookie: () => 'x', setCookie: () => {}, isMobile: () => true,
    canView: {Events: true}, canEdit: {Events: true},
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.initPage = initPage;this.getFilterQuery = () => filterQuery;', ctx);
  ctx.initPage();
  popstate({state: {filterQuery: '', sortQuery: ''}});
  return {filterQuery: ctx.getFilterQuery(), calls};
}

const MON = 'filter[Query][terms][0][val]';
const ARCH = 'filter[Query][terms][1][val]';
const STORAGE = 'filter[Query][terms][2][val]';

function form() {
  return [
    makeSelect(MON, ['1', '2', '3'], true),
    makeSelect(ARCH, ['', '0', '1'], false),
    makeSelect(STORAGE, ['', '0', '1'], true),
  ];
}

test('two selected monitors are restored on back', () => {
  const fields = form();
  const q = new URLSearchParams();
  q.set(MON, '1,2');
  q.set(ARCH, '0');
  q.set(STORAGE, '');
  const r = runPopstate(fields, '?view=events&' + q.toString());
  assert.deepStrictEqual(wrap(fields[0]).val(), ['1', '2']);
  assert.strictEqual(wrap(fields[1]).val(), '0');
  assert.ok(r.filterQuery.indexOf('&' + encodeURIComponent(MON) + '=1%2C2') >= 0, r.filterQuery);
  assert.ok(r.calls.indexOf('refresh') >= 0);
});

test('an empty multiple select stays empty instead of picking the blank option', () => {
  const fields = form();
  fields[2].options[1].selected = true;
  const q = new URLSearchParams();
  q.set(MON, '3');
  q.set(STORAGE, '');
  runPopstate(fields, '?view=events&' + q.toString());
  assert.deepStrictEqual(wrap(fields[0]).val(), ['3']);
  assert.deepStrictEqual(wrap(fields[2]).val(), []);
});

test('the chosen widget is told about the restored selection', () => {
  const fields = form();
  const q = new URLSearchParams();
  q.set(MON, '2,3');
  runPopstate(fields, '?view=events&' + q.toString());
  assert.ok(fields[0].triggered.indexOf('chosen:updated') >= 0);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
