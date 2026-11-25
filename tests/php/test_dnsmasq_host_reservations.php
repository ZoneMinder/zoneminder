<?php
/*
 * Detector for dnsmasq reservations lost on Save:
 *  - the options page only posts config[dhcp-host][mac]=ip for rows of the
 *    leases table, and process_dnsmasq_configfile() kept dhcp-host as a bare
 *    string that nothing rendered, so a reservation whose host was not in the
 *    leases file was dropped from the config on the next Save, and a leased
 *    host posted its current lease ip over the reserved one.
 *
 * Run: php tests/php/test_dnsmasq_host_reservations.php [root]
 */
namespace ZM {
  function Debug($m) {}
  function Error($m) {}
}
namespace {
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($src, $sig, $i - $sig + 1);
    }
  }
  return null;
}
function translate($s) { return $s; }
function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
class DT { function format($t) { return 'date'; } }

$root = $argv[1] ?? dirname(__DIR__, 2);
$view = file_get_contents($root.'/web/skins/classic/views/_options_dnsmasq.php');
eval(extract_function($view, 'process_dnsmasq_configfile'));
eval(extract_function($view, 'dnsmasq_lease_row'));
$rows_src = extract_function($view, 'dnsmasq_host_rows');
if ($rows_src !== null) eval($rows_src);

$f = tempnam(sys_get_temp_dir(), 'dnsm');
file_put_contents($f, "interface=eth0\n"
  ."dhcp-host=AA:BB:CC:00:00:01,192.168.1.10\n"
  ."dhcp-host=aa:bb:cc:00:00:02,192.168.1.11\n"
  ."dhcp-host=aa:bb:cc:00:00:03,192.168.1.12\n");
$c = process_dnsmasq_configfile($f);
unlink($f);
$reserved = $c['dhcp-host'] ?? null;
check('reservations are read as mac => ip', is_array($reserved)
  && ($reserved['aa:bb:cc:00:00:01'] ?? null) === '192.168.1.10'
  && ($reserved['aa:bb:cc:00:00:03'] ?? null) === '192.168.1.12');

$post = function($html) {
  preg_match_all('/name="config\[dhcp-host\]\[([^\]]+)\]" value="([^"]*)"/', $html, $m, PREG_SET_ORDER);
  $out = [];
  foreach ($m as $x) $out[$x[1]] = $x[2];
  return $out;
};

if (function_exists('dnsmasq_host_rows') && is_array($reserved)) {
  $leases = [
    ['expiry'=>'1900000000', 'mac'=>'aa:bb:cc:00:00:01', 'ip'=>'192.168.1.77', 'name'=>'cam1', 'id'=>'*'],
    ['expiry'=>'1900000000', 'mac'=>'aa:bb:cc:00:00:09', 'ip'=>'192.168.1.99', 'name'=>'phone', 'id'=>'*'],
  ];
  $posted = $post(dnsmasq_host_rows($leases, $reserved, [], new DT));
  check('a reservation without a lease keeps a row', ($posted['aa:bb:cc:00:00:03'] ?? null) === '192.168.1.12');
  check('every reservation is posted back', ($posted['aa:bb:cc:00:00:02'] ?? null) === '192.168.1.11');
  check('a leased host posts its reserved ip, not the lease ip', ($posted['aa:bb:cc:00:00:01'] ?? null) === '192.168.1.10');
  check('a lease without a reservation is unchanged', ($posted['aa:bb:cc:00:00:09'] ?? null) === '192.168.1.99');
  check('each mac has one row', count($posted) === 4);
} else {
  check('a reservation without a lease keeps a row', false);
  check('every reservation is posted back', false);
  check('a leased host posts its reserved ip, not the lease ip', false);
  check('a lease without a reservation is unchanged', false);
  check('each mac has one row', false);
}
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
}
