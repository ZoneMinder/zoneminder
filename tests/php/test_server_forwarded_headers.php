<?php
// Chained proxies send comma-separated X-Forwarded-* headers, with the
// client-facing value first. Server::Hostname(), Protocol() and Port() must
// use only the first element, otherwise stream URLs contain values like
// "a.example.com, p.internal" or "443,80".
//
// Run as: php tests/php/test_server_forwarded_headers.php

namespace ZM;

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_server_forwarded_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/database.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

define('ZM_PATH_ZMS', '/cgi-bin/nph-zms');
define('ZM_PATH_API', '/zm/api');

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'Server.php';

# A single-server install: Id 0, no stored Hostname to short-circuit the headers.
$_SERVER = array(
  'HTTP_X_FORWARDED_HOST'  => 'a.example.com, p.internal',
  'HTTP_X_FORWARDED_PROTO' => 'https,http',
  'HTTP_X_FORWARDED_PORT'  => '8443,80',
);
$s = new Server(array('Id'=>0));
check('hostname from forwarded list', $s->Hostname(), 'a.example.com');
check('protocol from forwarded list', $s->Protocol(), 'https');
check('port from forwarded list', $s->Port(), '8443');

# A new server with no row at all behaves the same way.
$s = new Server();
check('hostname from forwarded list, no row', $s->Hostname(), 'a.example.com');
check('protocol from forwarded list, no row', $s->Protocol(), 'https');
check('port from forwarded list, no row', $s->Port(), '8443');

# Single values (one proxy) keep working.
$_SERVER = array(
  'HTTP_X_FORWARDED_HOST'  => 'h.example.com',
  'HTTP_X_FORWARDED_PROTO' => 'http',
  'HTTP_X_FORWARDED_PORT'  => '8080',
);
$s = new Server(array('Id'=>0));
check('hostname single value', $s->Hostname(), 'h.example.com');
check('protocol single value', $s->Protocol(), 'http');
check('port single value', $s->Port(), '8080');

# The host's port is still stripped, and ipv6 literals still parse.
$_SERVER = array('HTTP_X_FORWARDED_HOST'=>'zm.example:8443, p.internal', 'HTTP_X_FORWARDED_PROTO'=>'https,http');
$s = new Server();
check('hostname list with port', $s->Hostname(), 'zm.example');
check('ipv6 hostname in list', (function() {
  $_SERVER = array('HTTP_X_FORWARDED_HOST'=>'[::1]:8443, p.internal');
  $s = new Server();
  return $s->Hostname();
})(), '[::1]');

exit($failures ? 1 : 0);
