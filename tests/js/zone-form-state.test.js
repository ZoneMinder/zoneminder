'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/zone.js'), 'utf8');

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

const names = [
  'newZone[Type]', 'newZone[CheckMethod]', 'newZone[MinPixelThreshold]', 'newZone[MaxPixelThreshold]',
  'newZone[MinAlarmPixels]', 'newZone[MaxAlarmPixels]', 'newZone[FilterX]', 'newZone[FilterY]',
  'newZone[MinFilterPixels]', 'newZone[MaxFilterPixels]', 'newZone[MinBlobPixels]',
  'newZone[MaxBlobPixels]', 'newZone[MinBlobs]', 'newZone[MaxBlobs]', 'newZone[OverloadFrames]',
  'newZone[ExtendAlarmFrames]', 'newAlarmRgbR', 'newAlarmRgbG', 'newAlarmRgbB', 'presetSelector',
  'codec',
];

function load(type) {
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  const list = names.map((n) => ({name: n, value: '', type: 'text', disabled: false}));
  const form = {elements: list};
  list.forEach((el) => {
    form.elements[el.name] = el;
    if (!el.name.startsWith('newZone')) form[el.name] = el;
  });
  form.elements['newZone[Type]'].value = type;
  form.elements['newZone[CheckMethod]'].value = 'Blobs';
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, isFinite, Array, Object,
    $j: chain,
    window: {addEventListener() {}},
    document: {
      querySelectorAll: () => [],
      getElementById: () => null,
      zoneForm: form,
    },
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  return {ctx, form};
}

const filterFields = [
  'newZone[FilterX]', 'newZone[FilterY]', 'newZone[MinFilterPixels]', 'newZone[MaxFilterPixels]',
  'newZone[MinBlobPixels]', 'newZone[MaxBlobPixels]', 'newZone[MinBlobs]', 'newZone[MaxBlobs]',
];

console.log('check method on types without one');
['Inactive', 'Privacy'].forEach((type) => {
  test(type + ' zone keeps the filter and blob fields disabled', () => {
    const {ctx, form} = load(type);
    ctx.applyZoneType();
    ctx.applyCheckMethod(); // initPage does this right after applyZoneType
    filterFields.forEach((n) => assert.strictEqual(form.elements[n].disabled, true, n));
  });
});
test('Active zone with Blobs enables the filter and blob fields', () => {
  const {ctx, form} = load('Active');
  ctx.applyZoneType();
  filterFields.forEach((n) => assert.strictEqual(form.elements[n].disabled, false, n));
});

console.log('\nchange detection');
test('changing the player is not a change to the zone', () => {
  const {ctx, form} = load('Active');
  const before = ctx.formState();
  form.elements['codec'].value = 'go2rtc';
  assert.strictEqual(ctx.formState(), before);
});
test('changing the preset selector is not a change to the zone', () => {
  const {ctx, form} = load('Active');
  const before = ctx.formState();
  form.elements['presetSelector'].value = '3';
  assert.strictEqual(ctx.formState(), before);
});
test('changing a zone field is a change', () => {
  const {ctx, form} = load('Active');
  const before = ctx.formState();
  form.elements['newZone[MinBlobs]'].value = '2';
  assert.notStrictEqual(ctx.formState(), before);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
