<?php
/*
 * Detector for dnsmasq options view/save defects:
 *  - process_dnsmasq_configfile() dropped everything after the second comma of
 *    a dhcp-option (a second DNS server), raised an undefined offset for a
 *    dhcp-option with no comma, kept the leading blanks of a value, and split
 *    lines longer than 255 bytes into two bogus lines.
 *  - the view concatenated ZM_PATH_DNSMASQ_CONF when it was not defined (an
 *    Error in PHP 8), indexed dhcp-range parts that may not exist, read
 *    $url_parts['host'] for monitors whose path has no host, showed 1970 for
 *    never-expiring leases, and named the generic option inputs
 *    dnsmasq_config[...], which the save handler never reads.
 *  - the save handler wrote value-less options as "name=".
 *
 * Run: php tests/php/test_dnsmasq_options.php
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
$root = dirname(__DIR__, 2).'/web';
$view = file_get_contents($root.'/skins/classic/views/_options_dnsmasq.php');
$save = file_get_contents($root.'/includes/actions/options.php').file_get_contents($root.'/includes/functions.php');

set_error_handler(function($no, $str) { throw new ErrorException($str, 0, $no); });
eval(extract_function($view, 'process_dnsmasq_configfile'));

$f = tempnam(sys_get_temp_dir(), 'dnsm');
file_put_contents($f, "# c\ninterface=eth0\ndhcp-option=option:dns-server,1.1.1.1,8.8.8.8\ndhcp-option=3\nno-resolv\ndomain =  lan \n"
  .'dhcp-option=option:domain-search,'.str_repeat('a', 300).",end\n");
try {
  $c = process_dnsmasq_configfile($f);
} catch (Throwable $e) {
  echo 'exception: '.$e->getMessage()."\n";
  $c = [];
}
unlink($f);
check('dhcp-option keeps every comma separated value', ($c['dhcp-option']['option:dns-server'] ?? null) === '1.1.1.1,8.8.8.8');
check('dhcp-option without a comma does not warn', array_key_exists('3', $c['dhcp-option'] ?? []));
check('value-less option is an empty string', ($c['no-resolv'] ?? null) === '');
check('value is trimmed', ($c['domain'] ?? null) === 'lan');
check('long line is not split', strlen($c['dhcp-option']['option:domain-search'] ?? '') === 304);

check('undefined ZM_PATH_DNSMASQ_CONF is not concatenated bare', strpos($view, '" . ZM_PATH_DNSMASQ_CONF') === false
  && !preg_match('/Debug\(.*["\x27]\s*\.\s*ZM_PATH_DNSMASQ_CONF/', $view));
check('dhcp-range is padded to three parts', strpos($view, 'array_pad(explode(\',\', $value), 3') !== false);
check('ffmpeg monitors without a host are skipped', strpos($view, "isset(\$url_parts['host'])") !== false);
check('lease expiry 0 is not formatted as a date', strpos($view, "\$lease['expiry'] == 0") !== false);
check('generic option inputs are posted as config[]', strpos($view, 'name="dnsmasq_config[') === false);
check('value-less options are saved without "="', strpos($save, "\$value === ''") !== false);
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
}
