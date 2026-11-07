<?php
// MonitorsController::alarmCause() filters the cause that monitors/alarm/command:on passes
// to zmu --cause. The cause is stored in Events.Cause and Events.Notes, which several views
// and clients print, so only letters, digits, spaces and plain punctuation are kept, and it
// is cut to the 31 bytes zmu stores without splitting a character.
//
// MonitorsController can't be loaded without CakePHP, so the method is taken from the source
// and run in a stand-in.
//
// Run as: php tests/php/test_monitor_alarm_cause.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/MonitorsController.php');
if (!preg_match('/^  private function alarmCause\(.*?^  }\n/ms', $source, $m)) {
  echo "FAIL - could not find alarmCause in MonitorsController.php\n";
  exit(1);
}
eval('class AlarmCauseHarness { '.str_replace('private function', 'public function', $m[0]).' }');

function cause($value) {
  $h = new AlarmCauseHarness();
  return $h->alarmCause($value);
}

check('plain cause is kept', cause('HA-test'), 'HA-test');
check('punctuation clients send is kept', cause('Doorbell (front): pressed!'), 'Doorbell (front): pressed!');
check('non-ASCII letters are kept', cause('Türklingel'), 'Türklingel');
check('missing cause', cause(null), '');
check('empty cause', cause(''), '');
check('array cause (cause[]=x)', cause(array('x')), '');
check('markup is dropped', cause('<svg onload=alert(1)>'), 'svg onload=alert(1)');
check('quotes, backslash and ampersand are dropped', cause("a'b\"c\\d&e`f"), 'abcdef');
check('$, | and backtick are dropped (escapeshellarg quotes the rest)', cause('x; rm -rf / $(id) | `y`'), 'x; rm -rf / (id) y');
check('NUL byte is dropped', cause("a\0b"), 'ab');
check('newline and tab are dropped', cause("line1\nline2\tx"), 'line1line2x');
check('spaces are collapsed and trimmed', cause('  a    b  '), 'a b');
check('only unsafe characters leaves nothing', cause('<>"\'&'), '');
check('invalid UTF-8 is refused', cause("ok\xC3("), '');
check('a leading dash stays a value', cause('--help'), '--help');
check('long cause is cut at 31 bytes', cause(str_repeat('a', 40)), str_repeat('a', 31));
$long = str_repeat('a', 30).'é';
check('cut does not split a character', cause($long), str_repeat('a', 30));
check('cut result is valid UTF-8', preg_match('//u', cause(str_repeat('é', 20))) === 1, true);
check('cut result fits 31 bytes', strlen(cause(str_repeat('é', 20))) <= 31, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
