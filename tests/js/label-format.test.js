'use strict';

// A monitor's label format is a strftime pattern ("%N - %d/%m/%y %H:%M:%S").
// convertLabelFormat() turns it into a moment.js format for the video timing
// track. Literal text must be wrapped in moment's [ ] escapes, otherwise
// moment reads every letter as a token ("Cam at " -> "Cpm7 pmt "), and every
// %X must be converted, including repeats.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skinPath = process.env.SKIN_JS || path.join(
    __dirname, '../../web/skins/classic/js/skin.js');
const src = fs.readFileSync(skinPath, 'utf8');

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

// Load the real convertLabelFormat() from skin.js.
const match = src.match(
    /function convertLabelFormat\(LabelFormat, monitorName\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'convertLabelFormat() not found in skin.js');
const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(
    match[0] + '\nthis.convertLabelFormat = convertLabelFormat;', sandbox);
const c = (format, name) => sandbox.convertLabelFormat(format, name);

console.log('convertLabelFormat');
test('brackets literal text and converts every token', () => {
  assert.strictEqual(c('Cam at %H:%M', 'X'), '[Cam at ]HH[:]mm');
});
test('converts repeated tokens', () => {
  assert.strictEqual(c('%H:%M:%S %H', 'X'), 'HH[:]mm[:]ss[ ]HH');
});
test('substitutes the monitor name', () => {
  assert.strictEqual(c('%N - %d', 'Cam1'), '[Cam1][ - ]DD');
});
test('escapes a literal percent', () => {
  assert.strictEqual(c('100%% %H', 'X'), '[100][%][ ]HH');
});
test('does not drop brackets from the monitor name', () => {
  assert.strictEqual(c('%N', 'Cam[1]'), '[Cam1]');
});

// End-to-end with the vendored moment, when it can be loaded in node.
let moment = null;
try {
  moment = require(path.join(__dirname, '../../web/skins/classic/js/moment.min.js'));
} catch (e) {
  console.log('  skip moment end-to-end: ' + e.message);
}
if (moment) {
  test('moment renders literal text unchanged', () => {
    const date = new Date(2024, 2, 5, 14, 7, 9);
    assert.strictEqual(
        moment(date).format(c('Cam at %H:%M', 'X')), 'Cam at 14:07');
  });
}

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
