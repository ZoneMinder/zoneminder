<?php
// Tests remoteControlUrl() in web/includes/control_functions.php: in a
// multi-server setup a PTZ or other control request for a monitor that another
// server records is handed to that server, which runs its zmcontrol.pl.
//
// Before, the server that got the request sent the zmcontrol option string to
// the other server's monitors/daemonControl API, which only starts and stops
// zmcontrol.pl, so the camera never moved and the request answered "Failed".
//
// Run: php tests/php/test_remote_control_url.php

define('ZM_SERVER_ID', 1);

$stubdir = sys_get_temp_dir().'/zm_remote_control_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
require_once __DIR__.'/../../web/includes/control_functions.php';

function get_auth_relay() { return 'auth=0123456789abcdef&user=admin'; }

class StubServer {
  private $id;
  public function __construct($id) { $this->id = $id; }
  public function Id() { return $this->id; }
  public function UrlToIndex() { return 'http://zm2.example:8080/zm/index.php'; }
}

class StubMonitor {
  private $serverId;
  private $serverKnown;
  public function __construct($serverId, $serverKnown = true) {
    $this->serverId = $serverId;
    $this->serverKnown = $serverKnown;
  }
  public function ServerId() { return $this->serverId; }
  public function Server() { return new StubServer($this->serverKnown ? $this->serverId : null); }
}

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

$request = array(
  'view' => 'request', 'request' => 'control', 'id' => '4', 'control' => 'moveConLeft',
  'xge' => '50', 'yge' => '50', 'token' => 'jwt', 'username' => 'admin', 'password' => 'secret',
  'ZMSESSID' => 'abc', 'stray' => '1',
);

check('monitor on this server is handled here', remoteControlUrl(new StubMonitor(1), $request), null);
check('monitor with no server is handled here', remoteControlUrl(new StubMonitor(0), $request), null);
check('monitor on a server ZoneMinder does not list is handled here',
  remoteControlUrl(new StubMonitor(2, false), $request), null);
check('a request forwarded once is handled where it lands',
  remoteControlUrl(new StubMonitor(2), $request + array('forwarded' => '1')), null);

$url = remoteControlUrl(new StubMonitor(2), $request);
check('monitor on another server goes to that server',
  strtok($url, '?'), 'http://zm2.example:8080/zm/index.php');
parse_str(parse_url($url, PHP_URL_QUERY), $sent);
check('the control request is passed on', array(
  isset($sent['view']) ? $sent['view'] : null,
  isset($sent['request']) ? $sent['request'] : null,
  isset($sent['id']) ? $sent['id'] : null,
  isset($sent['control']) ? $sent['control'] : null,
  isset($sent['xge']) ? $sent['xge'] : null,
  isset($sent['forwarded']) ? $sent['forwarded'] : null,
), array('request', 'control', '4', 'moveConLeft', '50', '1'));
check('the caller\'s credentials are not passed on', array(
  isset($sent['token']), isset($sent['password'])), array(false, false));
check('only known control parameters are forwarded, nothing else', array(
  isset($sent['ZMSESSID']), isset($sent['stray'])), array(false, false));
check('the auth relay says who is asking', array(
  isset($sent['auth']) ? $sent['auth'] : null, isset($sent['user']) ? $sent['user'] : null),
  array('0123456789abcdef', 'admin'));

$action = array('view' => 'request', 'request' => 'control', 'action' => 'monitorAction', 'id' => '3', 'aid' => '7');
parse_str(parse_url(remoteControlUrl(new StubMonitor(2), $action), PHP_URL_QUERY), $sent);
check('a monitor action keeps its action and ids', array($sent['action'], $sent['id'], $sent['aid']),
  array('monitorAction', '3', '7'));

array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
