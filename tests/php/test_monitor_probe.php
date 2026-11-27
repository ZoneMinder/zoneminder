<?php
/*
 * Detector for monitor_probe.php defects in the Add Monitors camera discovery.
 *
 * The functions under test are extracted from the real source and eval'd with
 * stubbed network helpers (wget, curl, port_open, arp, ZM\Monitor), so no real
 * network access happens.
 *
 * Run: php tests/php/test_monitor_probe.php
 */

$src = file_get_contents(dirname(__DIR__, 2).'/web/includes/monitor_probe.php');

$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label\n";
  }
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

function load($src, $names) {
  foreach ($names as $n) {
    $f = extract_function($src, $n);
    if ($f === null) continue;
    eval($f);
  }
}

// Stubs
eval('namespace ZM;
function Debug($m) {} function Info($m) {} function Warning($m) {} function Error($m) {}
function urlForLog($u) { return $u; }
class Monitor {
  public static $list = [];
  public $f;
  function __construct($f) { $this->f = $f; }
  static function find($filter = [], $opts = []) {
    return array_values(array_filter(self::$list, function($m) use ($filter) { return $m->f["Type"] == $filter["Type"]; }));
  }
  function Host() { return $this->f["Host"] ?? null; }
  function Path() { return $this->f["Path"] ?? null; }
  function User() { return null; }
  function Pass() { return null; }
  function Name() { return "mon"; }
  function Id() { return $this->f["Id"] ?? 1; }
}
class Control { static function find_one($f) { return null; } }
');
function translate($s) { return $s; }
function canEdit($area, $mid = null) { return true; }

$GLOBALS['wget_reply'] = [];
function wget($method, $url, $username, $password) {
  foreach ($GLOBALS['wget_reply'] as $needle => $reply) {
    if (strpos($url, $needle) !== false) return $reply;
  }
  return '';
}
function curl($method, $url, $username, $password) { return ''; }
function port_open($ip, $port) { return false; }

// Network discovery stubs for probeNetwork()
$GLOBALS['arp_scan'] = [];
$GLOBALS['subnet_calls'] = [];
$GLOBALS['networks'] = [];
function get_arp_results() { return []; }
function get_networks() { return $GLOBALS['networks']; }
function get_subnets($interface) {
  $GLOBALS['subnet_calls'][] = $interface;
  return $interface === 'eth0' ? ['192.168.1.0/24'] : [];
}
function get_arp_scan_results($network) { return $GLOBALS['arp_scan']; }

$dir = sys_get_temp_dir().'/zm_probe_test_'.getmypid();
mkdir($dir);
file_put_contents($dir.'/MacVendors.json', json_encode([
  'aabb01' => ['vendor'=>'Panasonic', 'type'=>'Panasonic'],
  'aabb02' => ['vendor'=>'AzureWave', 'type'=>'AzureWaveTechnologyInc'],
  'aabb03' => ['vendor'=>'Wansview', 'type'=>'Wansview'],
  'aabb04' => ['vendor'=>'Other', 'type'=>'Other'],
]));
define('ZM_PATH_DATA', $dir);
define('ZM_PATH_OUI', false);

load($src, ['probe_userinfo', 'probePanasonic', 'probeAzureWaveTechnologyInc', 'probeWansview',
  'probeHikvision', 'probeAxis', 'probeAmcrest', 'probeAvigilon', 'probeKnownMonitors', 'probeNetwork']);

function run_probe($interface = null) {
  $_REQUEST = [];
  if ($interface !== null) $_REQUEST['interface'] = $interface;
  $GLOBALS['subnet_calls'] = [];
  try {
    return [probeNetwork(), null];
  } catch (\Throwable $e) {
    return [[], $e];
  }
}

// 1. get_networks() returns name => "name subnet..." plus 'default' => name.
$GLOBALS['networks'] = ['eth0' => 'eth0 192.168.1.0/24', 'default' => 'eth0'];
$GLOBALS['arp_scan'] = ['aa:bb:04:00:00:01' => '192.168.1.20'];
list($res, $err) = run_probe();
check('scans the interface named eth0', in_array('eth0', $GLOBALS['subnet_calls'], true));
check('get_subnets is never given a "name subnet" description',
  count(array_filter($GLOBALS['subnet_calls'], function($i) { return strpos($i, ' ') !== false; })) === 0);
check('the default alias does not rescan the same interface',
  count($GLOBALS['subnet_calls']) === 1);
check('camera found by the arp-scan is returned', count($res) === 1);

// 2. Interface passed in the request as a string.
$GLOBALS['networks'] = [];
list($res, $err) = run_probe('eth0');
check('a single interface name from the request does not raise', $err === null);
check('a single interface name from the request is scanned', $GLOBALS['subnet_calls'] === ['eth0']);

// 3. Two Remote cameras sharing the same relative Path but different hosts.
$GLOBALS['networks'] = ['eth0' => 'eth0 192.168.1.0/24', 'default' => 'eth0'];
$GLOBALS['arp_scan'] = ['aa:bb:01:00:00:01' => '192.168.1.31', 'aa:bb:01:00:00:02' => '192.168.1.32'];
list($res, $err) = run_probe();
check('two Panasonic cameras on different hosts are both listed', count($res) === 2);

// 4. A vendor probe function that returns nothing.
$GLOBALS['arp_scan'] = ['aa:bb:02:00:00:01' => '192.168.1.40'];
list($res, $err) = run_probe();
check('a probe function returning nothing does not raise', $err === null);

// 5. Wansview returns the list of cameras.
$w = probeWansview('192.168.1.50');
check('probeWansview returns a list of cameras', isset($w[0]['ip']) && $w[0]['ip'] === '192.168.1.50');
$GLOBALS['arp_scan'] = ['aa:bb:03:00:00:01' => '192.168.1.50'];
list($res, $err) = run_probe();
check('a Wansview camera is reported by the scan', count($res) === 1 && ($res[0]['IP'] ?? null) === '192.168.1.50');

// 6. Monitors whose path has no host must not break the lookup.
\ZM\Monitor::$list = [new \ZM\Monitor(['Type'=>'Ffmpeg', 'Path'=>'/dev/video0'])];
$GLOBALS['arp_scan'] = ['aa:bb:04:00:00:01' => '192.168.1.20'];
$warnings = [];
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
list($res, $err) = run_probe();
restore_error_handler();
check('an Ffmpeg monitor without a host raises no warning', count($warnings) === 0);
\ZM\Monitor::$list = [];

// 7. Credentials with reserved characters are encoded in built URLs.
$pw = 'p@ss:w/rd#1';
$enc = rawurlencode($pw);
$h = probeHikvision('192.168.1.60', 'admin', $pw);
check('Hikvision rtsp path encodes the password', strpos($h[0]['monitor']['Path'], ':'.$enc.'@') !== false);
check('Hikvision snapshot url encodes the password', strpos($h[0]['mjpegstream'], ':'.$enc.'@') !== false);
$a = probeAxis('192.168.1.61', 'root', $pw);
check('Axis rtsp path encodes the password', strpos($a[0]['monitor']['Path'], ':'.$enc.'@') !== false);
$m = probeAmcrest('192.168.1.62', 'admin', 'a b');
check('Amcrest encodes a space as %20', strpos($m[0]['monitor']['Path'], ':a%20b@') !== false);

// 8. A reply that is not XML must not throw.
$GLOBALS['wget_reply'] = ['/ISAPI/' => '<html><body>401 Unauthorized'];
try {
  $h = probeHikvision('192.168.1.60', 'admin', 'x');
  $threw = false;
} catch (\Throwable $e) {
  $threw = true;
}
check('Hikvision tolerates a non-XML reply', !$threw);

// 9. Avigilon error replies without the expected keys.
$GLOBALS['wget_reply'] = [
  'get-general' => '{"error":"x"}',
  'get-streamuri' => '{"error":"x"}',
  'get-compression' => '{"error":"x"}',
];
$warnings = [];
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
$v = probeAvigilon('192.168.1.70', 'administrator', 'x');
restore_error_handler();
check('Avigilon keeps its default path when the stream uri is missing',
  is_string($v[0]['monitor']['Path']) && strpos($v[0]['monitor']['Path'], 'rtsp://') === 0);
check('Avigilon raises no warning for error replies', count($warnings) === 0);

rmdir_r($dir);
function rmdir_r($d) { foreach (glob("$d/*") as $f) unlink($f); rmdir($d); }

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
