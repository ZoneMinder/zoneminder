<?php
/*
 * Detector for dnsmasq lease row XSS:
 *  - a lease's name, mac and ip come from the DHCP client and must be
 *    HTML-escaped before being echoed into the leases table.
 *
 * Run: php tests/php/test_dnsmasq_lease_row_escape.php [root]
 */
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

if (!function_exists('translate')) {
  function translate($s) { return $s; }
}
if (!function_exists('validHtmlStr')) {
  function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
}
if (!class_exists('DT')) {
  class DT { function format($t) { return 'date'; } }
}

$root = $argv[1] ?? dirname(__DIR__, 2);
$view = file_get_contents($root.'/web/skins/classic/views/_options_dnsmasq.php');
if ($view === false) {
  fwrite(STDERR, "could not read _options_dnsmasq.php\n");
  exit(2);
}

$src = extract_function($view, 'dnsmasq_lease_row');
if ($src !== null) {
  eval($src);
  $row = function($lease, $monitors_by_ip) {
    return dnsmasq_lease_row($lease, $monitors_by_ip, new DT);
  };
} else {
  $lines = explode("\n", $view);
  $start = null;
  $end = null;
  foreach ($lines as $i => $line) {
    if ($start === null && strpos($line, 'foreach ($leases as $lease) {') !== false) {
      $start = $i;
      continue;
    }
    if ($start !== null && strpos($line, '} # end foreach') !== false) {
      $end = $i;
      break;
    }
  }
  if ($start === null || $end === null) {
    fwrite(STDERR, "could not locate lease row rendering\n");
    exit(2);
  }
  $body = implode("\n", array_slice($lines, $start + 1, $end - $start - 1));
  eval('function dnsmasq_lease_row($lease, $monitors_by_ip, $dateTimeFormatter) { ob_start(); '.$body.' return ob_get_clean(); }');
  $row = function($lease, $monitors_by_ip) {
    return dnsmasq_lease_row($lease, $monitors_by_ip, new DT);
  };
}

$monitors_by_ip = [];

$xss = [
  'expiry' => '0',
  'mac' => 'aa:bb:cc:dd:ee:ff',
  'ip' => '10.0.0.7',
  'name' => '<img src=x onerror=alert(1)>',
  'id' => '*',
];
$out = $row($xss, $monitors_by_ip);
check('hostname <img is escaped', strpos($out, '<img') === false);
check('hostname renders &lt;img', strpos($out, '&lt;img') !== false);

$attr = [
  'expiry' => '0',
  'mac' => 'aa"><script>x</script>',
  'ip' => '1"><b>',
  'name' => 'x',
  'id' => '*',
];
$out = $row($attr, $monitors_by_ip);
check('mac does not inject <script>', strpos($out, '<script>') === false);
check('ip does not inject "\'><b>', strpos($out, '"><b>') === false);

$normal = [
  'expiry' => '0',
  'mac' => 'aa:bb:cc:dd:ee:ff',
  'ip' => '10.0.0.7',
  'name' => 'cam1',
  'id' => '*',
];
$out = $row($normal, $monitors_by_ip);
check('normal lease name present', strpos($out, 'cam1') !== false);
check('normal lease mac input name', strpos($out, 'name="config[dhcp-host][aa:bb:cc:dd:ee:ff]"') !== false);
check('normal lease ip input value', strpos($out, 'value="10.0.0.7"') !== false);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
