'use strict';

// Linked Monitors editor: round trip of an existing expression through
// expr_to_ui()/ui_to_expr() with a minimal jQuery stand-in that follows
// jQuery's select semantics (val() is null when no option matches).

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

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
    path.join(__dirname, '../../web/js/MonitorLinkExpression.js'), 'utf8');
const ctx = vm.createContext({
  console: {log() {}},
  $j: $j,
  mid: 1,
  monitors: [{Id: '1', Name: 'A'}, {Id: '2', Name: 'B'}, {Id: '3', Name: 'C'}, {Id: '4', Name: 'D'}],
  zones: [{Id: '10', MonitorId: '2', Name: 'Z'}],
});
// setSelectValue() lives in the skin's skin.js, which every page loads
const skinSrc = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const helper = skinSrc.match(/^function setSelectValue\([\s\S]*?\n}\n/m);
if (!helper) throw new Error('setSelectValue not found in skin.js');
vm.runInContext(helper[0], ctx);
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

// Load expr into the editor, optionally change one select, return what the
// editor would save.
function edit(expr, change) {
  input.value = expr;
  vm.runInContext('expr_to_ui(' + JSON.stringify(expr) + ', $j("#LinkedMonitorsUI"))', ctx);
  const selects = ui.children.filter((c) => c.tag == 'select');
  if (change) change(selects);
  vm.runInContext('update_expr()', ctx);
  return input.value;
}

function choose(select, value) {
  const i = select.options.findIndex((o) => o.value === value);
  assert(i >= 0, 'no option ' + value);
  select.selected = i;
}

test('untouched expression without outer brackets is kept', () => {
  assert.strictEqual(edit('2|3|4'), '2|3|4');
});

test('bracketed expression is kept', () => {
  assert.strictEqual(edit('(2|3)&4'), '(2|3)&4');
  assert.strictEqual(edit('(2|3)&(4|2:10)'), '(2|3)&(4|2:10)');
});

test('blank bracket choice writes nothing, not a 0', () => {
  const out = edit('(2|3)&4', (s) => choose(s[0], ''));
  assert.strictEqual(out, '2|3)&4');
  assert(!/0/.test(out));
});

test('blank bracket on a closing bracket writes nothing', () => {
  const out = edit('(2|3)&4', (s) => choose(s[s.length - 2], ''));
  assert(!/0/.test(out), out);
});

test('comma operator is shown as or and saved as |', () => {
  assert.strictEqual(edit('2,3'), '2|3');
  assert.strictEqual(edit('2,3,4'), '2|3|4');
});

test('single linked monitor in brackets is kept', () => {
  assert.strictEqual(edit('(2)'), '(2)');
});

test('more brackets than the editor offers are kept', () => {
  assert.strictEqual(edit('((((2|3))))&4'), '((((2|3))))&4');
});

test('link to a monitor that is gone is kept', () => {
  assert.strictEqual(edit('2|9'), '2|9');
  assert.strictEqual(edit('2|3|9'), '2|3|9');
});

test('zone link round trips', () => {
  assert.strictEqual(edit('2:10|3'), '2:10|3');
});

test('editor never writes the text null', () => {
  const exprs = ['2,3', '(2)', '2|3|9', '((((2|3))))&4', '2|3|4', '(2|3)&4'];
  for (const e of exprs) assert(!/null/.test(edit(e)), e);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
