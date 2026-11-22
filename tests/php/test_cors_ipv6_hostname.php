<?php
// corsAllowedOrigin() must match a bracketed IPv6 Origin host against a
// server Hostname stored with or without brackets.
// Usage: php tests/php/test_cors_ipv6_hostname.php <repo root>

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/includes/Network.php';

class FakeServer {
  private $hostname;
  function __construct($hostname) { $this->hostname = $hostname; }
  function Hostname() { return $this->hostname; }
  function Name() { return 'node'; }
}

$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name: got '$got', expected '$expected'\n";
  }
}

function allowed($origin, $hostname) {
  $_SERVER['HTTP_ORIGIN'] = $origin;
  return corsAllowedOrigin(array(new FakeServer($hostname)), 0);
}

check('bare IPv6 Hostname matches a bracketed Origin',
  allowed('http://[fd00::5]:8080', 'fd00::5'), 'http://[fd00::5]:8080');
check('bracketed IPv6 Hostname matches a bracketed Origin',
  allowed('http://[fd00::5]:8080', '[fd00::5]'), 'http://[fd00::5]:8080');
check('another IPv6 host is refused',
  allowed('http://[fd00::6]:8080', 'fd00::5'), '');
check('IPv4 Hostname still matches',
  allowed('http://192.168.1.5:8080', '192.168.1.5'), 'http://192.168.1.5:8080');

exit($failures ? 1 : 0);
?>
