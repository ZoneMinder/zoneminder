// An option that depends on another which is itself greyed out (its own requirement unmet)
const path = require('path');
// stayed editable: USE_AUTH off, USE_GOOG_RECAPTCHA still stored as 1 -> the reCAPTCHA keys were enabled.
// The inline script of options.php runs against a small jQuery stand-in.
// Run as: node tests/js/options-requires-chain.test.js <path to web/skins/classic/views/options.php>
const fs = require('fs');
const vm = require('vm');
const php = fs.readFileSync(process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/options.php'), 'utf8');
const at = php.indexOf('// Dynamic enable/disable of config fields');
const code = php.slice(php.indexOf('\n', at) + 1, php.indexOf('</script>', at));

let failures = 0;
function check(n, got, want) {
  if (got === want) console.log('ok - ' + n);
  else {
    failures++; console.log('FAIL - ' + n + ' (got ' + got + ', want ' + want + ')');
  }
}

class Coll {
  constructor(items) {
    this.items = items; this.length = items.length;
  }
  find(sel) {
    const m = sel.match(/^\[name="newConfig\[(\w+)\]"\]$/);
    if (m) return new Coll(this.items.flatMap((o) => o.inputs || []).concat(this.items.filter((o) => o.root).flatMap((o) => o.all)).filter((i) => i.name === m[1]));
    if (sel === '[data-requires]') return new Coll(this.items.flatMap((o) => o.rows));
    if (sel === 'input, select, textarea') return new Coll(this.items.flatMap((o) => o.inputs));
    throw new Error('find ' + sel);
  }
  closest(sel) {
    return new Coll(this.items.filter((i) => i.row.requires !== undefined).map((i) => i.row));
  }
  is(sel) {
    const i = this.items[0]; return sel === ':checkbox' ? i.type === 'checkbox' : sel === ':radio' ? i.type === 'radio' : sel === ':checked' ? !!i.checked : false;
  }
  val() {
    return this.items[0].value;
  }
  attr(a) {
    return this.items[0].requires;
  }
  prop(p, v) {
    this.items.forEach((i) => {
      i.disabled = v;
    }); return this;
  }
  each(f) {
    this.items.forEach((i) => f.call(i)); return this;
  }
  on() {
    return this;
  }
  trigger() {
    return this;
  }
}
function opt(name, type, v, requires) {
  const row = {requires, inputs: []};
  const input = {name, type, checked: v === '1', value: v, row, disabled: false};
  row.inputs.push(input);
  return {row, input};
}
const o = {};
[['ZM_OPT_USE_AUTH', 'checkbox', '0'], ['ZM_OPT_USE_GOOG_RECAPTCHA', 'checkbox', '1', 'ZM_OPT_USE_AUTH=1'],
  ['ZM_OPT_GOOG_RECAPTCHA_SITEKEY', 'text', 'k', 'ZM_OPT_USE_GOOG_RECAPTCHA=1']].forEach((a) => {
  o[a[0]] = opt(...a.slice(0, 4));
});
const all = Object.values(o).map((x) => x.input);
const rows = Object.values(o).map((x) => x.row).filter((r) => r.requires !== undefined);
const root = {root: true, all, rows, inputs: all};
// $j(this) in each() is a row; its find('input...') must give that row's inputs
const $j = (x) => {
  if (x === '#options') return new Coll([root]);
  if (x && x.inputs && x.requires !== undefined) return new Coll([{rows: [], inputs: x.inputs, requires: x.requires}]).__row(x);
  return new Coll([x]);
};
Coll.prototype.__row = function(r) {
  this.items = [r]; this.length = 1; this.find = () => new Coll(r.inputs); this.attr = () => r.requires; return this;
};
let ready;
vm.runInNewContext(code, {$j, document: {addEventListener: (e, f) => {
  ready = f;
}}});
ready();
check('USE_AUTH off: recaptcha checkbox disabled', o.ZM_OPT_USE_GOOG_RECAPTCHA.input.disabled, true);
check('USE_AUTH off: site key disabled too', o.ZM_OPT_GOOG_RECAPTCHA_SITEKEY.input.disabled, true);
o.ZM_OPT_USE_AUTH.input.checked = true;
ready();
check('USE_AUTH on: recaptcha checkbox enabled', o.ZM_OPT_USE_GOOG_RECAPTCHA.input.disabled, false);
check('USE_AUTH on: site key enabled', o.ZM_OPT_GOOG_RECAPTCHA_SITEKEY.input.disabled, false);
console.log(failures ? failures + ' failure(s)' : 'all passed');
process.exit(failures ? 1 : 0);
