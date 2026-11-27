'use strict';

// changeControl() called console.err() when a control reply named an element
// the page does not have, which throws and abandoned the remaining controls.

const assert = require('assert');
const {loadWatch, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js changeControl');

test('an unknown control in the reply does not throw and later ones are applied', () => {
  const set = [];
  const errors = [];
  const g = loadWatch({
    console: {log() {}, warn() {}, error: (m) => errors.push(m), debug() {}},
  });
  g.monitorUrl = 'index.php';
  g.monitorId = 1;
  g.zmAuth = {appendTo: (u) => u};
  g.$j = Object.assign((sel) => ({
    length: sel === '#newContrast' ? 1 : 0,
    val: (v) => set.push(['val', sel, v]), attr() {},
  }), {
    getJSON: () => {
      const p = {done: (fn) => {
        fn({result: 'Ok', controls: [
          {control: 'brightness', value: 1}, {control: 'contrast', value: 2},
        ]});
        return p;
      }, fail: () => p};
      return p;
    },
  });
  g.changeControl({target: {name: 'newBrightness', value: '1'}});
  assert.strictEqual(errors.length, 1);
  assert.deepStrictEqual(set, [['val', '#newContrast', 2]]);
});

done();
