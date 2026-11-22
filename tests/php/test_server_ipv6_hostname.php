<?php
// An IPv6 literal stored in the Servers Hostname column must be bracketed in URLs.
//
// Run as: php tests/php/test_server_ipv6_hostname.php [web/includes dir]

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

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes';
$stubdir = sys_get_temp_dir().'/zm_server_ipv6_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/database.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.$root.PATH_SEPARATOR.get_include_path());

define('ZM_PATH_ZMS', '/cgi-bin/nph-zms');
define('ZM_PATH_API', '/zm/api');

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'Server.php';

$_SERVER = array();
function srv($host, $port) {
  return new Server(array('Id'=>2, 'Name'=>'cam2', 'Protocol'=>'http', 'Hostname'=>$host, 'Port'=>$port));
}
check('ipv6 with port', srv('fd00::5', 8080)->Url(), 'http://[fd00::5]:8080');
check('ipv6 without port', srv('fd00::5', null)->Url(), 'http://[fd00::5]');
check('ipv6 with streaming port', srv('fd00::5', 8080)->Url(30002), 'http://[fd00::5]:30002');
check('bracketed ipv6', srv('[fd00::5]', 8080)->Url(), 'http://[fd00::5]:8080');
check('hostname', srv('cam2.example', 8080)->Url(), 'http://cam2.example:8080');
check('ipv6 zms url', srv('fd00::5', 8080)->UrlToZMS(), 'http://[fd00::5]:8080/cgi-bin/nph-zms');

exit($failures ? 1 : 0);
