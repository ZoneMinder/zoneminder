<?php
/*
 * The vendor probes that built "user:pass@host" with a raw username must
 * encode it like the others, or a user name with @ : / # breaks the URL.
 *
 * Run: php tests/php/test_probe_username_encoding.php <repo root>
 */
$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/includes/monitor_probe.php');
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if (!$cond) $failures++;
  echo ($cond ? '  ok  - ' : '  NOT OK - ').$label."\n";
}
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}' && --$depth === 0) return substr($src, $sig, $i - $sig + 1);
  }
}
eval('namespace ZM; function Debug($m) {} function urlForLog($u) { return $u; }');
function curl($m, $u, $a, $b) { return ''; }
function wget($m, $u, $a, $b) { return ''; }
function port_open($ip, $port) { return false; }
foreach (['probe_userinfo', 'probeAmcrest', 'probeUniview', 'probeGrandstreamNetworksInc'] as $n) {
  eval(extract_function($src, $n));
}
$user = 'jo@home:x';
$enc = rawurlencode($user).':p%40ss@';
$m = probeAmcrest('192.168.1.62', $user, 'p@ss');
check('Amcrest rtsp path encodes the username', strpos($m[0]['monitor']['Path'], '://'.$enc) !== false);
check('Amcrest snapshot url encodes the username', strpos($m[0]['mjpegstream'], '://'.$enc) !== false);
$u = probeUniview('192.168.1.63', $user, 'p@ss');
check('Uniview rtsp path encodes the username', strpos($u[0]['monitor']['Path'], '://'.$enc) !== false);
check('Uniview snapshot url encodes the username', strpos($u[0]['mjpegstream'], '://'.$enc) !== false);
$g = probeGrandstreamNetworksInc('192.168.1.64', $user, 'p@ss');
check('Grandstream snapshot url encodes the username', strpos($g[0]['mjpegstream'], '://'.$enc) !== false);
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
