<?php
/*
 * get_networks() must list interfaces whose names contain '-' or '.' (br-1234,
 * eth0.100, veth@if5) and must not raise undefined-index warnings for routes
 * whose device is not in the `ip link` list (e.g. lo).
 *
 * Run: php tests/php/test_get_networks_parsing.php
 */
$root = dirname(__DIR__, 2);
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $start = strrpos(substr($src, 0, $sig), "\n") + 1;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') { $depth--; if ($depth === 0) return substr($src, $start, $i - $start + 1); }
  }
  return null;
}

$link = [
  '1: lo: <LOOPBACK,UP,LOWER_UP> mtu 65536 qdisc noqueue state UNKNOWN mode DEFAULT group default qlen 1000',
  '    link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00',
  '2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc fq_codel state UP mode DEFAULT group default qlen 1000',
  '    link/ether 52:54:00:12:34:56 brd ff:ff:ff:ff:ff:ff',
  '3: br-1234abcd: <NO-CARRIER,BROADCAST,MULTICAST,UP> mtu 1500 qdisc noqueue state DOWN mode DEFAULT group default',
  '4: eth0.100@eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc noqueue state UP mode DEFAULT group default qlen 1000',
];
$route = [
  'default via 192.168.1.1 dev eth0 proto dhcp metric 100',
  '127.0.0.0/8 dev lo scope host',
  '172.18.0.0/16 dev br-1234abcd proto kernel scope link src 172.18.0.1 linkdown',
  '192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.5',
  '192.168.100.0/24 dev eth0.100 proto kernel scope link src 192.168.100.5',
];

define('ZM_PATH_IP', __FILE__);
$GLOBALS['fake_exec'] = ['link'=>$link, 'route'=>$route];
function fake_exec($cmd, &$output = null, &$status = null) {
  $which = substr($cmd, -4) === 'link' ? 'link' : 'route';
  if ($output === null) $output = [];
  foreach ($GLOBALS['fake_exec'][$which] as $l) $output[] = $l;
  $status = 0;
}
$warnings = [];
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

eval('namespace ZM { function Error($m) {} function Debug($m) {} }');
$fn = extract_function(file_get_contents($root.'/web/includes/functions.php'), 'get_networks');
check('get_networks() found', $fn !== null);
// exec() cannot be redefined, so call a stub instead.
eval(str_replace('exec(', 'fake_exec(', $fn));

$i = get_networks();
check('no PHP warnings', $warnings === []);
check('lo is not listed', !isset($i['lo']));
check('eth0 listed with subnet', isset($i['eth0']) && strpos($i['eth0'], '192.168.1.0/24') !== false);
check('br-1234abcd listed with subnet', isset($i['br-1234abcd']) && strpos($i['br-1234abcd'], '172.18.0.0/16') !== false);
check('eth0.100 listed with subnet', isset($i['eth0.100']) && strpos($i['eth0.100'], '192.168.100.0/24') !== false);
check('default is eth0', ($i['default'] ?? null) === 'eth0');

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
