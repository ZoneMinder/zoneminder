<?php
// Tests which request Origins corsAllowedOrigin() (Network.php) lets make
// credentialed cross-origin calls. CORSHeaders() (functions.php, web UI) and
// AppController::beforeFilter() (API) send Access-Control-Allow-Origin and
// Access-Control-Allow-Credentials: true for exactly these.
//
// Network.php touches neither ZM's config nor its database, so this runs from
// a source checkout: php tests/php/test_cors_allowed_origin.php

require_once __DIR__.'/../../web/includes/Network.php';

// Stands in for ZM\Server: corsAllowedOrigin() reads only Hostname() and Name().
class FakeServer {
  private $hostname;
  private $name;
  function __construct($name, $hostname) {
    $this->name = $name;
    $this->hostname = $hostname;
  }
  // ZM\Server::Hostname() falls back to Name when Hostname is empty.
  function Hostname() { return $this->hostname !== '' ? $this->hostname : $this->name; }
  function Name() { return $this->name; }
}

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

function allowed($origin, $servers, $minStreamingPort=0, $httpHost='zm.example.com') {
  $_SERVER = array('HTTP_HOST' => $httpHost);
  if ($origin !== null) $_SERVER['HTTP_ORIGIN'] = $origin;
  return corsAllowedOrigin($servers, $minStreamingPort);
}

// Multi-server: the Origin's host must be a listed server's Hostname or Name.
$servers = array(new FakeServer('node1', 'zm.example.com'), new FakeServer('node2.example.com', ''));
check('a listed Hostname is allowed',
  allowed('https://zm.example.com', $servers), 'https://zm.example.com');
check('on any port',
  allowed('http://zm.example.com:8080', $servers), 'http://zm.example.com:8080');
check('case-insensitively',
  allowed('https://ZM.Example.COM', $servers), 'https://ZM.Example.COM');
check('a server with no Hostname is matched by its Name',
  allowed('https://node2.example.com:30000', $servers), 'https://node2.example.com:30000');
check('a listed Name is allowed',
  allowed('http://node1', $servers), 'http://node1');
check('a host that only starts with a server name is refused',
  allowed('https://zm.example.com.evil.net', $servers), '');
check('a host that only starts with a server name and a dash is refused',
  allowed('https://zm.example.com-evil.net', $servers), '');
check('a host that only ends with a server name is refused',
  allowed('https://evilzm.example.com', $servers), '');
check('userinfo cannot smuggle a server name in',
  allowed('https://zm.example.com@evil.net', $servers), '');
check('a path is not an Origin',
  allowed('https://zm.example.com/x', $servers), '');
check('an unlisted host is refused',
  allowed('https://other.example', $servers), '');
check('the opaque null Origin is refused',
  allowed('null', $servers), '');
check('a scheme other than http(s) is refused',
  allowed('file://zm.example.com', $servers), '');
check('no Origin allows nothing',
  allowed(null, $servers), '');
check('streaming ports do not widen the servers list',
  allowed('https://other.example', $servers, 30000), '');

// Single server: nothing without streaming ports; with them, this host only.
check('no servers and no streaming ports allow nothing',
  allowed('http://zm.example.com:30001', array()), '');
check('streaming ports allow this host on another port',
  allowed('http://zm.example.com:30001', array(), 30000), 'http://zm.example.com:30001');
check('the Host header port does not matter',
  allowed('http://zm.example.com:30001', array(), 30000, 'zm.example.com:8080'), 'http://zm.example.com:30001');
check('streaming ports do not allow another host',
  allowed('http://evil.example', array(), 30000), '');
check('or one that only starts with this host',
  allowed('http://zm.example.com.evil.net', array(), 30000), '');
check('an IPv6 host matches itself',
  allowed('http://[::1]:30001', array(), 30000, '[::1]:8080'), 'http://[::1]:30001');
check('without a Host header nothing is allowed',
  allowed('http://zm.example.com', array(), 30000, null), '');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
