<?php
if ($argc < 2) {
  $failures = 0;
  foreach (array('err', 'off', 'unknown') as $case) {
    $out = array();
    $rc = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case).' 2>&1', $out, $rc);
    echo implode("\n", $out)."\n";
    if ($rc !== 0) $failures++;
  }
  echo $failures ? "\n$failures case(s) FAILED\n" : "\nall cases passed\n";
  exit($failures ? 1 : 0);
}

$case = $argv[1];
$GLOBALS['logged'] = array();

function canEdit($area) {
  return true;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function ajaxError($message) {
}
function ajaxResponse($data = array()) {
}
class FakeUser {
  public function Username() {
    return 'tester';
  }
}
$user = new FakeUser();
eval('namespace ZM;
  class Logger {
    public static $codes = array(-6=>"OFF", -5=>"AUD", -3=>"FAT", -2=>"ERR", 0=>"INF", 1=>"DBG");
    public static function fetch() {
      return new Logger();
    }
    public function logPrint($level, $message, $file = null, $line = null) {
      $GLOBALS["logged"][] = $level;
    }
  }
  function logInit($options = array()) {}
  function Error($m) {}
  function Warning($m) {}');

$levels = array('err' => 'ERR', 'off' => 'OFF', 'unknown' => 'NOPE');
$_REQUEST = array('task' => 'create');
$_POST = array('level' => $levels[$case], 'message' => 'boom', 'file' => 'x.js', 'line' => '3');

require(__DIR__.'/../../web/ajax/log.php');

$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

switch ($case) {
  case 'err':
    check('a real level is logged as itself', $GLOBALS['logged'], array(-2));
    break;
  case 'off':
    check('OFF is not a level a message can be logged at', $GLOBALS['logged'], array(-2));
    break;
  case 'unknown':
    check('an unknown level is logged as an error', $GLOBALS['logged'], array(-2));
    break;
}
exit($failures ? 1 : 0);
