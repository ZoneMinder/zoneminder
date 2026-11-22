<?php
// Filter::control() posts to the web UI of the other servers with the session's
// credentials in the URL; they must arrive unchanged and the URL must hold no space.
//
// Run as: php tests/php/test_filter_control_relay.php

namespace ZM;

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n   got:  ".var_export($got, true)."\n   want: ".var_export($want, true)."\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_filter_control_relay_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) { echo "# Error: $s\n"; }
function Debug($s) {}
function Info($s) {}
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
function daemonControl() { echo "# daemonControl called for a remote server\n"; }

define('ZM_OPT_USE_AUTH', true);
define('ZM_AUTH_RELAY', isset($argv[1]) ? $argv[1] : 'plain');
define('ZM_SERVER_ID', 1);

class Server {
  public function Id() { return 2; }
  public function UrlToIndex() { return 'http://other.example/zm/index.php'; }
  public static function find($p = array()) { return array(new Server()); }
  public static function find_one($p = array()) { return new Server(); }
}

// Stands in for the other server: records the URL Filter::control() posts to.
class CaptureHttp {
  public static $urls = array();
  public $context;
  public function stream_open($path, $mode, $options, &$opened) { self::$urls[] = $path; return true; }
  public function stream_read($count) { return false; }
  public function stream_eof() { return true; }
  public function stream_stat() { return array(); }
}
stream_wrapper_unregister('http');
stream_wrapper_register('http', CaptureHttp::class);

require_once __DIR__.'/../../web/includes/Filter.php';

$username = 'ops team';
$password = 'p&ss+w#rd %41';
$_SESSION = array('username' => $username, 'password' => $password);

$filter = new Filter();
$filter->Id(5);
$filter->control('restart');

check(ZM_AUTH_RELAY.': one request to the other server', count(CaptureHttp::$urls), 1);
check(ZM_AUTH_RELAY.': the URL holds no raw space', count(CaptureHttp::$urls) ? strpos(CaptureHttp::$urls[0], ' ') : null, false);
$query = array();
if (count(CaptureHttp::$urls)) parse_str((string)parse_url(CaptureHttp::$urls[0], PHP_URL_QUERY), $query);
check(ZM_AUTH_RELAY.': view is filter', isset($query['view']) ? $query['view'] : null, 'filter');
check(ZM_AUTH_RELAY.': user arrives as the session username', isset($query['user']) ? $query['user'] : null, $username);
if (ZM_AUTH_RELAY == 'plain') {
  check('plain: pass arrives as the session password', isset($query['pass']) ? $query['pass'] : null, $password);
} else {
  check(ZM_AUTH_RELAY.': no pass is sent', isset($query['pass']), false);
}

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

if (!isset($argv[1])) {
  // The none relay sends only the username; run it in its own process, since
  // ZM_AUTH_RELAY is a constant.
  $out = array();
  exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' none', $out, $status);
  echo implode("\n", $out)."\n";
  if ($status) $failures++;
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
