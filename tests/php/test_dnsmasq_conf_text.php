<?php
/*
 * Detector for dnsmasqConfText() config rendering defects:
 *  - values or names containing newlines could inject extra directives.
 *  - dangerous directives (dhcp-script, dhcp-luascript, conf-file, conf-dir)
 *    were rendered instead of rejected.
 *  - value-less options were rendered as "name=".
 *
 * Run: php tests/php/test_dnsmasq_conf_text.php
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

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/includes/functions.php');

$fn = extract_function($src, 'dnsmasqConfText');
if ($fn === null) {
  // Before the helper existed the save handler built the text inline; run that block instead.
  $save = file_get_contents($root.'/web/includes/actions/options.php');
  $start = strpos($save, "\$conf = '';");
  $end = strpos($save, 'if (false===file_put_contents(ZM_PATH_DNSMASQ_CONF');
  if ($start === false || $end === false) {
    echo "NOT OK - dnsmasq save code found\n";
    exit(1);
  }
  $fn = 'function dnsmasqConfText($config) { '.substr($save, $start, $end - $start).' return $conf; }';
}
eval($fn);

$expected = 'interface=eth0'.PHP_EOL
  .'dhcp-range=10.0.0.5,10.0.0.50,12h'.PHP_EOL
  .'dhcp-option=option:router,10.0.0.1'.PHP_EOL
  .'dhcp-host=aa:bb:cc:dd:ee:ff,10.0.0.7'.PHP_EOL
  .'bind-interfaces'.PHP_EOL;

check('renders interface, dhcp-range, dhcp-option, dhcp-host and enabled flags in order',
  dnsmasqConfText([
    'interface' => 'eth0',
    'dhcp-range' => ['min' => '10.0.0.5', 'max' => '10.0.0.50', 'expires' => '12h'],
    'dhcp-option' => ['option:router' => '10.0.0.1'],
    'dhcp-host' => ['aa:bb:cc:dd:ee:ff' => '10.0.0.7'],
    'bind-interfaces' => 'yes',
    'dhcp-authoritative' => 'no',
  ]) === $expected);

check('renders a value-less option without a trailing "="',
  dnsmasqConfText(['interface' => '']) === 'interface'.PHP_EOL);

check('rejects conf-script (runs a program at start)',
  dnsmasqConfText(['conf-script' => '/tmp/x.sh']) === false);
check('rejects dumpfile (writes any path)',
  dnsmasqConfText(['dumpfile' => '/tmp/x.pcap']) === false);
check('rejects dhcp-script',
  dnsmasqConfText(['dhcp-script' => '/tmp/x.sh']) === false);

check('rejects dhcp-luascript',
  dnsmasqConfText(['dhcp-luascript' => '/tmp/x.lua']) === false);

check('rejects conf-file',
  dnsmasqConfText(['conf-file' => '/tmp/y']) === false);

check('rejects conf-dir',
  dnsmasqConfText(['conf-dir' => '/tmp']) === false);

check('rejects a newline in a plain option value',
  dnsmasqConfText(['interface' => "eth0\ndhcp-script=/tmp/x.sh"]) === false);

check('rejects a newline in a dhcp-option value',
  dnsmasqConfText(['dhcp-option' => ['option:router' => "1.2.3.4\nuser=root"]]) === false);

check('rejects a newline in a dhcp-host key',
  dnsmasqConfText(['dhcp-host' => ["aa\ndhcp-script=/x" => '1.2.3.4']]) === false);

check('rejects a newline in an option name',
  dnsmasqConfText(["x\ndhcp-script" => 'a']) === false);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
