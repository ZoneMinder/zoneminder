'use strict';

// A monitor's label format is a strftime pattern ("%N - %d/%m/%y %H:%M:%S").
// formatLabel() renders it with luxon for the video timing track, the way zmc
// draws it on the frame. Literal text and the monitor name must come out as
// is: letters must not be read as luxon tokens ("Cam at " -> "Cpm7 pmt "), and
// an apostrophe must survive, which luxon 3.4 cannot print from a format
// string. Every %X must be converted, including repeats.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skinPath = process.env.SKIN_JS || path.join(
    __dirname, '../../web/skins/classic/js/skin.js');
const src = fs.readFileSync(skinPath, 'utf8');
const luxonSrc = fs.readFileSync(path.join(
    __dirname, '../../web/skins/classic/js/luxon-3.4.4.min.js'), 'utf8');

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

// Load the real formatLabel() from skin.js next to the vendored luxon.
const match = src.match(
    /function formatLabel\(LabelFormat, monitorName, dt\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'formatLabel() not found in skin.js');
const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(luxonSrc + '\nthis.luxon = luxon;', sandbox);
vm.runInContext(match[0] + '\nthis.formatLabel = formatLabel;', sandbox);
const DateTime = sandbox.luxon.DateTime;

// Sunday 2024-03-03 14:07:09.250 in New York (EST), as addVideoTimingTrack()
// builds it.
const dt = DateTime.fromSQL('2024-03-03 14:07:09.250',
    {zone: 'America/New_York', locale: 'en-US'});
const f = (format, name) => sandbox.formatLabel(format, name, dt);

console.log('formatLabel');
test('keeps literal text and converts every token', () => {
  assert.strictEqual(f('Cam at %H:%M', 'X'), 'Cam at 14:07');
});
test('converts repeated tokens', () => {
  assert.strictEqual(f('%H:%M:%S %H', 'X'), '14:07:09 14');
});
test('substitutes the monitor name as is', () => {
  assert.strictEqual(f('%N - %d/%m/%y', 'Cam1'), 'Cam1 - 03/03/24');
});
test('keeps token letters, brackets and apostrophes in the name', () => {
  assert.strictEqual(f('%N %H', "Dad's [MD] cam"), "Dad's [MD] cam 14");
});
test('keeps an apostrophe in literal text', () => {
  assert.strictEqual(f("o'clock %H", 'X'), "o'clock 14");
});
test('prints a literal percent', () => {
  assert.strictEqual(f('100%% %H', 'X'), '100% 14');
});
test('keeps a trailing percent and an unknown code', () => {
  assert.strictEqual(f('%Q %H%', 'X'), '%Q 14%');
});
test('names and 12 hour clock match strftime in English', () => {
  assert.strictEqual(f('%a %A %b %B %I %l %p', 'X'),
      'Sun Sunday Mar March 02 2 PM');
  assert.strictEqual(f('%r', 'X'), '02:07:09 PM');
});
test('numeric dates match strftime', () => {
  assert.strictEqual(f('%F %e %j %k %Y', 'X'), '2024-03-03 3 063 14 2024');
});
test('weekday numbers count like strftime', () => {
  // %u is 1-7 from Monday, %w is 0-6 from Sunday.
  assert.strictEqual(f('%u %w', 'X'), '7 0');
});
test('time zone offset and abbreviation', () => {
  assert.strictEqual(f('%z %Z', 'X'), '-0500 EST');
});
test('%f is hundredths of a second', () => {
  assert.strictEqual(f('%S.%f', 'X'), '09.25');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
