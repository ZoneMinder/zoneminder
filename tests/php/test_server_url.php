<?php
// Server::Url() must give a usable URL for a remote server with no Port,
// and must not carry a port from a forwarded host into the streaming port URL.
//
// Run as: php tests/php/test_server_url.php

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

$stubdir = sys_get_temp_dir().'/zm_server_url_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/database.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

define('ZM_PATH_ZMS', '/cgi-bin/nph-zms');
define('ZM_PATH_API', '/zm/api');

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'Server.php';

$_SERVER = array();

# A remote server row saved without a Port
$s = new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'http', 'Hostname'=>'cam2.example', 'Port'=>null));
check('remote server without port', $s->Url(), 'http://cam2.example');
$s = new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'https', 'Hostname'=>'cam2.example', 'Port'=>''));
check('remote https server with empty port', $s->Url(), 'https://cam2.example');
$s = new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'http', 'Hostname'=>'cam2.example', 'Port'=>8080));
check('remote server with port', $s->Url(), 'http://cam2.example:8080');
$s = new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'http', 'Hostname'=>'cam2.example', 'Port'=>80));
check('remote server default port', $s->Url(), 'http://cam2.example');

# Single server behind a proxy that forwards the host with its port,
# with the streaming port in use (ZM_MIN_STREAMING_PORT + monitor id)
$_SERVER = array('HTTP_X_FORWARDED_HOST'=>'zm.example:8443', 'HTTP_X_FORWARDED_PROTO'=>'https');
$s = new Server();
check('forwarded host with port and streaming port', $s->Url(30001), 'https://zm.example:30001');
$_SERVER = array('HTTP_X_FORWARDED_HOST'=>'[::1]:8443', 'HTTP_X_FORWARDED_PROTO'=>'https');
check('forwarded ipv6 host with port', $s->Url(30001), 'https://[::1]:30001');
$_SERVER = array('HTTP_X_FORWARDED_HOST'=>'zm.example', 'HTTP_X_FORWARDED_PROTO'=>'https');
check('forwarded host without port', $s->Url(30001), 'https://zm.example:30001');

exit($failures ? 1 : 0);
