'use strict';

// The scheduled tasks page builds a cron spec in two places: the pickers do it
// in the browser so the field fills in as you choose, and the server does it
// again on save because the browser is not what keeps a crontab line honest.
//
// Two implementations of the same mapping drift. These check the JS against
// the PHP it mirrors, by reading the day-of-week map straight out of
// web/includes/CronTab.php rather than restating it here -- a copy in the test
// would drift with equal enthusiasm.

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const CRON = require(path.join(__dirname, '../../web/skins/classic/views/js/cron.js'));
const phpSource = fs.readFileSync(
    path.join(__dirname, '../../web/includes/CronTab.php'), 'utf8');

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

// Pull cronFrequencies()'s body out of the PHP and read its pairs.
function phpFrequencies() {
  const start = phpSource.indexOf('function cronFrequencies()');
  assert.ok(start >= 0, 'cronFrequencies() not found in CronTab.php');
  const body = phpSource.slice(start, phpSource.indexOf('}', phpSource.indexOf('return array(', start)));
  const map = {};
  const pair = /'(\w+)'\s*=>\s*'([^']*)'/g;
  let m;
  while ((m = pair.exec(body)) !== null) map[m[1]] = m[2];
  return map;
}

console.log('the JS and PHP maps agree');

test('every frequency the PHP knows, bar hourly, is in the JS map', () => {
  const php = phpFrequencies();
  assert.ok(Object.keys(php).length >= 10, 'parsed too few frequencies: ' + JSON.stringify(php));
  for (const name of Object.keys(php)) {
    // hourly is not offered by the pickers: it needs no hour, so it would make
    // the time control mean something different from the rest of the list.
    if (name === 'hourly') continue;
    assert.strictEqual(CRON.CRON_FREQUENCY_DOW[name], php[name],
        name + ': JS has ' + CRON.CRON_FREQUENCY_DOW[name] + ', PHP has ' + php[name]);
  }
});

test('the JS map invents nothing the PHP does not have', () => {
  const php = phpFrequencies();
  for (const name of Object.keys(CRON.CRON_FREQUENCY_DOW)) {
    assert.ok(name in php, 'JS offers "' + name + '" which PHP cannot build');
  }
});

console.log('building a spec from the pickers');

test('every day at 8am', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '08:00'), '0 8 * * *');
});

test('every monday at 7pm', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('monday', '19:00'), '0 19 * * 1');
});

test('every weekday at 7:30', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('weekdays', '07:30'), '30 7 * * 1-5');
});

test('every weekend day', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('weekends', '09:15'), '15 9 * * 0,6');
});

test('midnight is a time like any other', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '00:00'), '0 0 * * *');
});

test('seconds are tolerated, since some browsers send them', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '08:00:00'), '0 8 * * *');
});

console.log('parts that do not describe a time');

test('an unknown frequency builds nothing', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('fortnightly', '08:00'), null);
});

test('a half typed time builds nothing rather than something wrong', () => {
  // The field keeps whatever it had, so the operator is not fighting the form
  // as they type.
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '0'), null);
  assert.strictEqual(CRON.cronSpecFromPickers('daily', ''), null);
  assert.strictEqual(CRON.cronSpecFromPickers('daily', 'half eight'), null);
});

test('an out of range time builds nothing', () => {
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '24:00'), null);
  assert.strictEqual(CRON.cronSpecFromPickers('daily', '08:60'), null);
});

console.log('whatever it builds, the server will accept');

test('every spec the pickers can build is five plain fields', () => {
  // The server revalidates with cronScheduleIsValid(); a spec the pickers
  // produced must never be one it rejects, or the form would refuse its own
  // output.
  for (const freq of Object.keys(CRON.CRON_FREQUENCY_DOW)) {
    for (const time of ['00:00', '08:00', '19:30', '23:59']) {
      const spec = CRON.cronSpecFromPickers(freq, time);
      assert.ok(spec !== null, freq + ' ' + time + ' built nothing');
      assert.strictEqual(spec.split(/\s+/).length, 5, 'not five fields: ' + spec);
      assert.ok(!/[\r\n%]/.test(spec), 'spec carries a character the server refuses: ' + spec);
    }
  }
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
