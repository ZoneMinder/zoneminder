'use strict';

// Choosing a PTZ preset that has no saved label left the previous preset's
// label in the New Label box: labels[] only has entries for presets with a
// ControlPresets row, and jQuery val(undefined) is a read, not a clear.

const assert = require('assert');
const {loadWatch, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js preset label');

function load(selected, labels) {
  const field = {value: 'stale'};
  const g = loadWatch();
  g.labels = labels;
  g.$j = (sel) => {
    if (sel === '#ctrlPresetForm option:selected') return {val: () => selected};
    return {
      val: (v) => {
        // Like jQuery: undefined means "get", so the field is left alone.
        if (v !== undefined) field.value = v;
        return field.value;
      },
    };
  };
  return {g, field};
}

test('a preset with a label shows it', () => {
  const {g, field} = load('2', [undefined, 'a', 'front door']);
  g.updatePresetLabels();
  assert.strictEqual(field.value, 'front door');
});

test('a preset without a label clears the box', () => {
  const {g, field} = load('3', [undefined, 'a', 'front door']);
  g.updatePresetLabels();
  assert.strictEqual(field.value, '');
});

done();
