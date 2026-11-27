'use strict';

// Multi-value filter term selects must post every chosen value (name ends in
// [] and the select is multiple) and keep a stored comma list selected.
// Usage: node tests/js/filter-multi-values.test.js [path/to/filter.js]
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/filter.js');
const src = fs.readFileSync(jsPath, 'utf8');
const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const escapeSrc = skin.match(/^function escapeHTML\(text\) \{[\s\S]*?^\}/m)[0];

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

// Any method returns the same object, so long jQuery chains run without a DOM.
function chain(overrides) {
  const p = new Proxy(function() {}, {
    get: (t, k) => (k in overrides ? overrides[k] : () => p),
    apply: () => p,
  });
  return p;
}

// Runs parseRows on one row of the given attr whose old value cell holds
// prevVal; returns the attrs set on the new value select and what .val() got.
function rebuild(attr, prevVal) {
  const attrs = {};
  const applied = [];
  const ctx = {
    console,
    document: {},
    monitors: {1: {Name: 'A'}, 3: {Name: 'B'}},
    sorted_monitor_ids: ['1', '3'],
    zones: {}, servers: {1: 'S1', 3: 'S3'}, storageareas: {1: {Name: 'a'}, 3: {Name: 'b'}},
    availableTags: [{Id: 1, Name: 'x'}, {Id: 3, Name: 'y'}],
    opTypes: {'=': 'equal to'},
    tags_opTypes: {'=': 'equal to'},
    applyChosen() {},
    $j: (html) => {
      if (html != '<select></select>') return chain({});
      const sel = chain({
        attr: (k, v) => {
          if (!/\[op\]/.test(String(v))) attrs[k] = v;
          return sel;
        },
        append: () => sel,
        addClass: () => sel,
      });
      return sel;
    },
  };
  const cell = (n) => {
    const kids = chain({
      val: (...a) => {
        if (a.length) {
          if (n == 4) applied.push(a[0]);
          return kids;
        }
        return n == 2 ? attr : (n == 4 ? prevVal : undefined);
      },
      html: () => kids,
    });
    return chain({children: () => kids, html: () => kids});
  };
  const inputTds = chain({
    eq: cell,
    find: () => chain({attr: () => 'filter[Query][terms][0][attr]'}),
  });
  const rows = chain({length: 1, eq: () => chain({children: () => inputTds})});
  vm.createContext(ctx);
  vm.runInContext(escapeSrc, ctx);
  vm.runInContext(src, ctx);
  ctx.parseRows(rows);
  return {attrs, applied};
}

console.log('parseRows multi-value selects');
for (const attr of ['Monitor', 'MonitorName', 'ServerId', 'StorageId', 'Tags']) {
  test(attr + ' select is multiple and posts an array', () => {
    const {attrs} = rebuild(attr, '1,3');
    assert.ok(/\]\[val\]\[\]$/.test(attrs.name), 'name was ' + attrs.name);
    assert.strictEqual(attrs.multiple, 'multiple');
  });
  test(attr + ' keeps a stored comma list selected', () => {
    const {applied} = rebuild(attr, '1,3');
    assert.strictEqual(JSON.stringify(applied[applied.length - 1]), '["1","3"]');
  });
}

console.log('validateForm');
test('finds the value element once its name ends in []', () => {
  const ctx = {console, document: {}, alert() {}, errorValue: 'err', $j: () => chain({})};
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  const rows = {length: 1};
  ctx.$j = () => chain({find: () => chain({eq: () => chain({find: () => rows})})});
  const elements = {'filter[Query][terms][0][val][]': {value: ''}};
  let alerted = false;
  ctx.alert = () => {
    alerted = true;
  };
  const form = {elements};
  assert.strictEqual(ctx.validateForm(form), false);
  assert.ok(alerted, 'empty multi select should raise the value alert');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
