<?php
// A remote server row saved without a Port must not take the port of the
// current request (X-Forwarded-Port or ZM_BASE_URL) when building its URL.
//
// Run as: php tests/php/test_server_remote_port_fallback.php [web/includes root]

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

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
$stubdir = sys_get_temp_dir().'/zm_server_remote_port_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/database.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.$root.'/includes'.PATH_SEPARATOR.get_include_path());

define('ZM_PATH_ZMS', '/cgi-bin/nph-zms');
define('ZM_PATH_API', '/zm/api');
define('ZM_BASE_URL', 'https://zm.example');
define('ZM_SERVER_ID', 1);

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'Server.php';

$_SERVER = array('HTTP_X_FORWARDED_PORT'=>'8443');
$s = new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'http', 'Hostname'=>'cam2.example', 'Port'=>null));
check('remote server ignores forwarded port', $s->Url(), 'http://cam2.example');

$_SERVER = array();
check('remote server ignores ZM_BASE_URL port', $s->Url(), 'http://cam2.example');

# The local single-server install still uses the request/base URL.
$s = new Server(array('Id'=>0));
check('single server uses ZM_BASE_URL port', $s->Port(), 443);
$_SERVER = array('HTTP_X_FORWARDED_PORT'=>'8443');
check('single server uses forwarded port', $s->Port(), '8443');

# The server handling this request has a row too in a multi-server setup; its
# port still comes from the request.
$s = new Server(array('Id'=>1, 'Name'=>'local', 'Protocol'=>'https', 'Hostname'=>'zm.example', 'Port'=>null));
$_SERVER = array('HTTP_X_FORWARDED_PORT'=>'8443');
check('this server uses the forwarded port', $s->Port(), '8443');

exit($failures ? 1 : 0);
