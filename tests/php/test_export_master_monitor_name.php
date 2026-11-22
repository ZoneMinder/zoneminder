<?php
// Checks that exportEventImagesMaster() escapes the monitor name in the tab
// titles it builds from the Monitors table.
//
// Run as: php tests/php/test_export_master_monitor_name.php   (from the repo root)

$root = $argv[1] ?? dirname(__DIR__, 2);

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

eval('namespace ZM; function Error($m) {} function Warning($m) {} function Debug($m) {}
class Event { public static function find($f = []) { return [new \FakeEvent()]; } }');

function translate($string) { return $string; }
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}
function dbFetchAll() {
  return [['Id' => 5, 'Name' => '<img src=x onerror=alert(1)>']];
}

define('ZM_PATH_WEB', $root.'/web');
define('ZM_SKIN_PATH', 'skins/classic');
$css = 'base';

class FakeEvent {
  public function Id() { return 1; }
  public function MonitorId() { return 5; }
  public function Relative_Path() { return '5/1'; }
  public function SaveJPEGs() { return false; }
  public function DefaultVideo() { return ''; }
}

require_once($root.'/web/skins/classic/includes/export_functions.php');

ob_start();
try {
  $html = exportEventImagesMaster(array(1), false, false, 'flat');
} catch (Throwable $e) {
  $html = ob_get_clean();
  while (ob_get_level()) ob_end_clean();
  echo 'Error: '.$e->getMessage()."\n";
  exit(1);
}
$html .= ob_get_clean();
while (ob_get_level()) ob_end_clean();

check('monitor name is not emitted raw', strpos($html, '<img src=x onerror=alert(1)>') !== false, false);
check('monitor name is html escaped', strpos($html, '&lt;img src=x onerror=alert(1)&gt;') !== false, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
