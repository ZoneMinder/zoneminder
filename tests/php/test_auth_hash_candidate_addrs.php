<?php
// Tests ZM\authHashCandidateAddrs() in web/includes/auth.php: which client
// addresses an IP-bound auth hash (ZM_AUTH_HASH_IPS) may be validated against,
// and how long the address a hash was issued to stays acceptable after the
// client moves. The function is pure, but auth.php pulls in ZM's config, so
// bootstrap config.php first as the other tests in this directory do.
//
// Run: sudo -u www-data php tests/php/test_auth_hash_candidate_addrs.php
//      (from the repo root, on a tree with a readable zm.conf)
//
// How the retained address gets into the session in the first place is covered
// by test_remote_addr.php, which needs no config or database.
set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');   // connects to DB + loads config; must precede auth.php
require_once('auth.php');

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

// Signature: authHashCandidateAddrs($liveAddr, $prevAddr, $prevAt, $now, $ttlHours)
$now = 1000000;
$ttl = 2; // hours, matching the ZM_AUTH_HASH_TTL default

check('no previous address yields just the live one',
  authHashCandidateAddrs('192.168.1.55', '', 0, $now, $ttl),
  array('192.168.1.55'));

check('unchanged address is not duplicated',
  authHashCandidateAddrs('192.168.1.55', '192.168.1.55', $now - 60, $now, $ttl),
  array('192.168.1.55'));

check('recently changed address is accepted alongside the live one',
  authHashCandidateAddrs('10.0.0.7', '192.168.1.55', $now - 60, $now, $ttl),
  array('10.0.0.7', '192.168.1.55'));

// The point of the window: an address stops being accepted once a hash issued
// to it would have expired anyway, so this never extends a hash's lifetime.
check('previous address expires after the hash TTL',
  authHashCandidateAddrs('10.0.0.7', '192.168.1.55', $now - (2 * 3600) - 1, $now, $ttl),
  array('10.0.0.7'));

check('previous address still accepted just inside the TTL',
  authHashCandidateAddrs('10.0.0.7', '192.168.1.55', $now - (2 * 3600) + 1, $now, $ttl),
  array('10.0.0.7', '192.168.1.55'));

check('previous address with no timestamp is rejected',
  authHashCandidateAddrs('10.0.0.7', '192.168.1.55', 0, $now, $ttl),
  array('10.0.0.7'));

check('null previous address is rejected',
  authHashCandidateAddrs('10.0.0.7', null, $now - 60, $now, $ttl),
  array('10.0.0.7'));

// Addresses are matched exactly. Accepting a neighbouring host would let anyone
// on the client's network replay a stolen hash, so no netmask is applied.
check('a same-subnet neighbour is never added',
  authHashCandidateAddrs('192.168.1.99', '', 0, $now, $ttl),
  array('192.168.1.99'));

check('the exact previous address is added, not its subnet',
  authHashCandidateAddrs('192.168.1.99', '192.168.1.55', $now - 60, $now, $ttl),
  array('192.168.1.99', '192.168.1.55'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
