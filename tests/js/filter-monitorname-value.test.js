'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/filter.js'), 'utf8');
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
  const target = function() {};
  const p = new Proxy(target, {
    get: (t, k) => (k in overrides ? overrides[k] : () => p),
    apply: () => p,
  });
  return p;
}

// The value an HTML parser gives a double-quoted value attribute.
function optionValue(html) {
  const m = html.match(/^<option value="([^"]*)"/);
  return m[1].replace(/&quot;/g, '"').replace(/&#39;/g, '\'')
      .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
}

function monitorNameOptions(names) {
  const appended = [];
  const monitors = {};
  names.forEach((name, i) => monitors[i + 1] = {Name: name});
  const cell = (n) => {
    const kids = chain({
      val: (...a) => (a.length ? kids : (n == 2 ? 'MonitorName' : undefined)),
    });
    return chain({children: () => kids});
  };
  const inputTds = chain({
    eq: cell,
    find: () => chain({attr: () => 'filter[Query][terms][0][attr]'}),
  });
  const rows = chain({length: 1, eq: () => chain({children: () => inputTds})});
  const ctx = {
    console,
    document: {},
    monitors,
    sorted_monitor_ids: Object.keys(monitors),
    opTypes: {'=': 'equal to'},
    applyChosen() {},
    $j: (html) => {
      if (html != '<select></select>') return chain({});
      const select = chain({append: (o) => {
        appended.push(o); return select;
      }});
      return select;
    },
  };
  vm.createContext(ctx);
  vm.runInContext(escapeSrc, ctx);
  vm.runInContext(src, ctx);
  ctx.parseRows(rows);
  return appended.filter((o) => o.indexOf('equal to') < 0).map(optionValue);
}

console.log('MonitorName filter options');
test('option value is the full monitor name', () => {
  const names = ['Front "door"', 'Back <yard> & gate', 'Bob\'s cam'];
  assert.deepStrictEqual(monitorNameOptions(names), names);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
