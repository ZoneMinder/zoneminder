'use strict';

// Filter editor behaviours in web/skins/classic/views/js/filter.js and the
// matching markup in web/skins/classic/views/filter.php.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/filter.js'), 'utf8');
const viewSrc = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/filter.php'), 'utf8');

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

function load(extra) {
  const ctx = Object.assign({console, document: {}, alert() {}, confirm: () => true}, extra);
  const inner = ctx.$j || (() => ({}));
  ctx.$j = (...a) => {
    const r = inner(...a);
    if (r && typeof r === 'object' && !r.ready) r.ready = () => {};
    return r;
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  return ctx;
}

console.log('submitAction');
test('Save runs the form validation instead of skipping it', () => {
  const calls = [];
  const form = {
    elements: {action: {value: ''}},
    submit() {
      calls.push('submit');
    },
    requestSubmit() {
      calls.push('requestSubmit');
    },
  };
  const ctx = load();
  ctx.submitAction({form, value: 'Save'});
  assert.strictEqual(form.elements.action.value, 'Save');
  // form.submit() does not fire the submit event, so validateForm never ran.
  assert.deepStrictEqual(calls, ['requestSubmit']);
});

console.log('validateForm');
function backgroundForm(withVideo) {
  const checked = (c) => ({checked: c, value: ''});
  const els = {
    'filter[Query][terms][0][val]': {value: 'x'},
    'filter[Query][terms][0][attr]': {value: 'Name'},
    'filter[Query][limit]': {value: ''},
    'filter[AutoDelete]': checked(false),
    'filter[UpdateDiskSpace]': checked(false),
    'filter[Background]': checked(true),
    'filter[AutoArchive]': checked(false),
    'filter[AutoUnarchive]': checked(false),
    'filter[AutoExecute]': checked(false),
    'filter[AutoCopy]': checked(false),
    'filter[AutoMove]': checked(false),
    'filter[AutoUpload]': checked(true),
  };
  if (withVideo) els['filter[AutoVideo]'] = checked(false);
  return {elements: els};
}
function validate(form) {
  const alerts = [];
  const ctx = load({
    $j: () => ({find: () => ({eq: () => ({find: () => [1]})})}),
    alert: (m) => alerts.push(m),
  });
  const result = ctx.validateForm(form);
  return {result, alerts};
}
test('Background filter does not throw when the Video action is not offered', () => {
  const r = validate(backgroundForm(false));
  assert.strictEqual(r.result, true);
});
test('Background filter with only Upload ticked is not told it has no actions', () => {
  const r = validate(backgroundForm(true));
  assert.deepStrictEqual(r.alerts, []);
});

console.log('resetFilter');
test('Reset reloads the saved filter, since form.reset() cannot restore rebuilt term cells', () => {
  const loc = [];
  const form = {getAttribute: () => '?view=filter&Id=7', reset() {}};
  const ctx = load({
    window: {location: {assign: (u) => loc.push(u)}},
    $j: () => [{reset() {}}],
  });
  ctx.resetFilter({form});
  assert.deepStrictEqual(loc, ['?view=filter&Id=7']);
});

console.log('markup');
test('the Execute action checkbox refreshes the button state', () => {
  const m = viewSrc.match(/<input type="checkbox" id="filter\[AutoExecute\]"[^\n]*/);
  assert.ok(m, 'checkbox found');
  assert.ok(/data-on-click-this="updateButtons"/.test(m[0]), m[0]);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
