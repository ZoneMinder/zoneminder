'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const jsPath = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/zone.js');
const src = fs.readFileSync(jsPath, 'utf8');

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

function load() {
  const resetState = {disabled: null};
  const chain = new Proxy(function() {}, {
    get: (t, p) => {
      if (p === 'length') {
        return 0;
      }
      if (p === 'val') {
        return () => '3';
      }
      if (p === 'prop') {
        return (name, value) => {
          if (name === 'disabled') {
            resetState.disabled = value;
          }
          return chain;
        };
      }
      return () => chain;
    },
    apply: () => chain,
  });
  const names = [
    'newZone[Type]', 'newZone[CheckMethod]', 'newZone[FilterX]', 'newZone[FilterY]',
    'newZone[MinFilterPixels]', 'newZone[MaxFilterPixels]', 'newZone[MinBlobPixels]',
    'newZone[MaxBlobPixels]', 'newZone[MinBlobs]', 'newZone[MaxBlobs]', 'newZone[Area]',
    'presetSelector',
  ];
  const list = names.map((n) => ({name: n, value: '', type: 'number', disabled: false}));
  const form = {elements: list};
  list.forEach((el) => {
    form.elements[el.name] = el;
  });
  form.presetSelector = form.elements['presetSelector'];
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, Array, Object,
    $j: chain,
    window: {addEventListener() {}},
    document: {
      getElementById: () => null,
      querySelectorAll: () => [],
      zoneForm: form,
    },
    monitorArea: 10000,
    monitorPixelArea: 640 * 480,
    presets: {3: {MinBlobs: 4}},
    zonePresetFields: ['MinBlobs'],
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  form.elements['newZone[Type]'].value = 'Active';
  form.elements['newZone[CheckMethod]'].value = 'Blobs';
  ctx.zone = {MonitorId: 1, Area: 10000, Points: []};
  return {ctx, form, resetState};
}

console.log('loading a zone preset');
test('a preset that changes the form enables the Reset button', () => {
  const {ctx, form, resetState} = load();
  ctx.initialState = ctx.formState();
  resetState.disabled = true;
  ctx.applyPreset();
  assert.strictEqual(form.elements['newZone[MinBlobs]'].value, 4);
  assert.strictEqual(resetState.disabled, false);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
