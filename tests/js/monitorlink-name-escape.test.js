'use strict';

// Monitor/zone names in the Linked Monitors editor must be set as text, not
// interpolated into HTML strings. A malicious monitor or zone name must not
// reach append() as markup, while still round tripping through the editor.
//
// Usage: node tests/js/monitorlink-name-escape.test.js [path/to/MonitorLinkExpression.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const rawHtml = [];

class El {
  constructor(tag) {
    this.tag = tag;
    this.options = [];
    this.selected = -1;
    this.value = '';
    this.handlers = {};
    this.children = [];
    this.text = '';
  }
}

function wrap(el) {
  const w = {
    el: el,
    append(x) {
      if (typeof x === 'string') {
        rawHtml.push(x);
        const m = x.match(/^<option value="([^"]*)">(.*)<\/option>$/);
        if (m) {
          el.options.push({value: m[1], text: m[2]});
        } else if (x.startsWith('<br')) {
          el.children.push(new El('br'));
        }
        return w;
      }
      const child = x.el;
      if (child.tag == 'option') {
        el.options.push({value: child.value, text: child.text});
      } else {
        el.children.push(child);
      }
      return w;
    },
    html() {
      el.children = [];
      return w;
    },
    val(v) {
      if (el.tag == 'select') {
        if (arguments.length) {
          el.selected = el.options.findIndex((o) => o.value === String(v));
          return w;
        }
        return el.selected < 0 ? null : el.options[el.selected].value;
      }
      if (arguments.length) {
        el.value = v;
        return w;
      }
      return el.value;
    },
    text(t) {
      el.text = t;
      return w;
    },
    on(ev, fn) {
      el.handlers[ev] = fn;
      return w;
    },
    children() {
      return el.children;
    },
  };
  return w;
}

const input = new El('input');
const ui = new El('div');
function $j(x) {
  if (x instanceof El) return wrap(x);
  if (x && x.el) return x;
  if (x === '<select></select>' || x === '<select id="monitorLinks"></select>') {
    const e = new El('select');
    // A select shows its first option until something is chosen
    const w = wrap(e);
    const append = w.append;
    w.append = function(y) {
      append(y);
      if (e.selected < 0 && e.options.length) e.selected = 0;
      return w;
    };
    return w;
  }
  if (x === '<option></option>') return wrap(new El('option'));
  if (x === '[name="newMonitor[LinkedMonitors]"]') return wrap(input);
  if (x === '#LinkedMonitorsUI') return wrap(ui);
  throw new Error('unexpected selector ' + x);
}

const src = fs.readFileSync(
    process.argv[2] || path.join(__dirname, '../../web/js/MonitorLinkExpression.js'), 'utf8');
const ctx = vm.createContext({
  console: {log() {}},
  $j: $j,
  mid: 1,
  monitors: [
    {Id: '1', Name: 'A'},
    {Id: '2', Name: '<img src=x onerror=alert(1)>'},
  ],
  zones: [{Id: '10', MonitorId: '2', Name: '<b onmouseover=x>Z</b>'}],
  setSelectValue: function(select, v) {
    select.val(v);
  },
});
vm.runInContext(src, ctx);

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

const expr = '2|2:10';
input.value = expr;
vm.runInContext(
    'expr_to_ui(' + JSON.stringify(expr) + ', $j("#LinkedMonitorsUI"))', ctx);
const selects = ui.children.filter((c) => c.tag == 'select');
const texts = [];
for (const s of selects) {
  for (const o of s.options) texts.push(o.text);
}

test('names are not interpolated into HTML strings', () => {
  for (const h of rawHtml) {
    assert(h.indexOf('<img') < 0, 'markup from monitor name appended: ' + h);
    assert(h.indexOf('<b ') < 0, 'markup from zone name appended: ' + h);
  }
});

test('option texts contain the verbatim names', () => {
  assert(texts.indexOf('<img src=x onerror=alert(1)> : All Zones') >= 0,
      'missing all-zones option text: ' + JSON.stringify(texts));
  assert(texts.indexOf(
      '<img src=x onerror=alert(1)> : <b onmouseover=x>Z</b>') >= 0,
  'missing zone option text: ' + JSON.stringify(texts));
});

test('zone link option keeps its value', () => {
  const values = [];
  for (const s of selects) {
    for (const o of s.options) values.push(o.value);
  }
  assert(values.indexOf('2:10') >= 0, 'missing 2:10 option: ' + values.join(','));
});

test('expression round trips', () => {
  vm.runInContext('update_expr()', ctx);
  assert.strictEqual(input.value, '2|2:10');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
