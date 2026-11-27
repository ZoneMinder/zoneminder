<?php
// Filter::control() with a ServerId that matches no server must warn and do
// nothing. Server::find_one() returns null for it, which used to be wrapped in
// an array and then called ->Id() on, a fatal error.
//
// Run as: php tests/php/test_filter_control_unknown_server.php

namespace ZM;

$failures = 0;
$stubdir = sys_get_temp_dir().'/zm_filter_control_unknown_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$warnings = 0;
$daemon_calls = 0;
function Warning($s) { global $warnings; $warnings++; }
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
function daemonControl() { global $daemon_calls; $daemon_calls++; }

define('ZM_OPT_USE_AUTH', false);
define('ZM_SERVER_ID', 1);

class Server {
  public function Id() { return 1; }
  public static function find($p = array()) { return array(new Server()); }
  public static function find_one($p = array()) { return null; }
}

require_once __DIR__.'/../../web/includes/Filter.php';

$filter = new Filter();
$filter->Id(5);
try {
  $filter->control('stop', 999);
  $ok = true;
} catch (\Throwable $e) {
  $ok = false;
  echo '# '.get_class($e).': '.$e->getMessage()."\n";
}
foreach (array(
  'unknown ServerId does not throw' => $ok,
  'unknown ServerId warns' => $warnings === 1,
  'unknown ServerId runs no daemon command' => $daemon_calls === 0,
) as $name => $pass) {
  echo ($pass ? 'ok' : 'FAIL')." - $name\n";
  if (!$pass) $failures++;
}
exit($failures ? 1 : 0);
