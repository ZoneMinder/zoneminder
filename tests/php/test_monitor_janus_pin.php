<?php
// Monitor::Janus_Pin() used to read the pin by running zmu --janus-pin, which
// connects to the database, loads the config and authenticates just to print
// shared_data->janus_pin. It now reads the same SharedData through the PHP
// Monitor's own mapping, and must apply the same permission rule zmu did: a
// user with Stream view and access to the monitor, or no pin.
//
// Monitor.php can't be included without the web bootstrap and a database, so
// the methods are taken from the source and run in a stand-in.
//
// Run as: php tests/php/test_monitor_janus_pin.php

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

$source = file_get_contents(__DIR__.'/../../web/includes/Monitor.php');

function methodSource($source, $pattern) {
  if (!preg_match($pattern, $source, $m)) {
    echo "FAIL - could not find method matching $pattern\n";
    exit(1);
  }
  return $m[0];
}

$decode = methodSource($source, '/^  public static function decodeJanusPin\(.*?^  }\n/ms');
$may = methodSource($source, '/^  public function mayReadJanusPin\(.*?^  }\n/ms');
$janus = methodSource($source, '/^  public function Janus_Pin\(.*?^  }\n/ms');

// canView('Stream', $id) is what the code asks for. The stub records the
// arguments and answers with the value the test set, standing in for auth.php's
// canView, which is visibleMonitor($id) and canView('Stream').
function canView($area, $mid = false) {
  $GLOBALS['canViewArea'] = $area;
  $GLOBALS['canViewMid'] = $mid;
  return $GLOBALS['canViewResult'];
}

eval('class JanusPinHarness { '
  .'public $Id = 1; '
  .'public $JanusEnabled = true; '
  .'public $raw = ""; '
  .'public $connected = true; '
  .'public function connect() { return $this->connected; } '
  .'public function shared_read($section, $var) { return $this->raw; } '
  .$decode.$may.$janus
  .' }');

$h = new JanusPinHarness();
$GLOBALS['canViewResult'] = false;

check('the pin is cut at the first NUL', JanusPinHarness::decodeJanusPin("abc\0def"), 'abc');
check('a leading NUL leaves no pin', JanusPinHarness::decodeJanusPin("\0abc"), '');
check('an all-NUL buffer leaves no pin', JanusPinHarness::decodeJanusPin(str_repeat("\0", 64)), '');
check('a buffer without a NUL is kept whole', JanusPinHarness::decodeJanusPin('abcdef'), 'abcdef');
check('an empty buffer leaves no pin', JanusPinHarness::decodeJanusPin(''), '');
check('a non-string leaves no pin', JanusPinHarness::decodeJanusPin(false), '');
check('a zero-padded pin is decoded', JanusPinHarness::decodeJanusPin("secret\0".str_repeat("\0", 58)), 'secret');

check('a user without stream view or monitor access gets no pin', $h->mayReadJanusPin(), false);
check('the permission check is for stream view', $GLOBALS['canViewArea'], 'Stream');
check('and for this monitor', $GLOBALS['canViewMid'], 1);

$GLOBALS['canViewResult'] = true;
check('a user with stream view and monitor access may read the pin', $h->mayReadJanusPin(), true);

$h->raw = "secret\0".str_repeat("\0", 58);

$GLOBALS['canViewResult'] = false;
check('Janus_Pin denies a user without permission', $h->Janus_Pin(), null);

$GLOBALS['canViewResult'] = true;
check('Janus_Pin returns the decoded pin for an allowed user', $h->Janus_Pin(), 'secret');

$h->connected = false;
check('Janus_Pin gives no pin when shared memory cannot be read', $h->Janus_Pin(), null);
$h->connected = true;

$h->JanusEnabled = false;
check('Janus_Pin gives no pin when Janus is not enabled', $h->Janus_Pin(), '');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
