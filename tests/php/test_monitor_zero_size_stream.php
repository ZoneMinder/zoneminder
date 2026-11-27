<?php
// Monitor::getStreamSrc() and Monitor::getStreamHTML() divided by ViewWidth() /
// ViewHeight() with no zero guard, so a monitor whose size is not known yet
// (Width and Height 0) threw DivisionByZeroError when a width or height was
// requested:
//  - getStreamSrc($args) with $args['width'] or $args['height'] and no 'scale';
//  - getStreamHTML($options) with $options['width'] like '640px'.
// Run as: php tests/php/test_monitor_zero_size_stream.php
// The Monitor.php under test can be chosen with
//   ZM_TEST_MONITOR_PHP=/path/to/Monitor.php
// (default: web/includes/Monitor.php next to this test).

// The fixed getStreamSrc() leaves 'scale' unset when there is no size to scale
// from (it is defaulted to 100 just below), so silence that warning.
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

$root = __DIR__.'/../..';
$monitor_php = getenv('ZM_TEST_MONITOR_PHP');
if (!$monitor_php) $monitor_php = $root.'/web/includes/Monitor.php';

$failures = 0;
$passes = 0;
function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

// Stubs for the database and the related models, as in test_monitor_model.php.
$stub_dir = sys_get_temp_dir().'/zm_monitor_zero_stream_'.getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbQuery($sql, $params = null) { return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Zone') as $c) {
  file_put_contents("$stub_dir/$c.php", "<?php namespace ZM; class $c extends ZM_Object { protected static \$table='{$c}s'; protected \$defaults=array('Id'=>null,'Name'=>''); public static function find_one(\$p=array(),\$o=array()) { return null; } public static function find(\$p=array(),\$o=array()) { return array(); } }");
}
file_put_contents($stub_dir.'/Server.php', '<?php namespace ZM; class Server extends ZM_Object { protected static $table="Servers"; protected $defaults=array("Id"=>null,"Name"=>""); public static function find_one($p=array(),$o=array()) { $s=new Server(); $s->Id=$p["Id"]; return $s; } public function UrlToZMS($port=null) { return "http://zm/cgi-bin/nph-zms"; } }');
file_put_contents($stub_dir.'/Storage.php', '<?php namespace ZM; class Storage extends ZM_Object { protected static $table="Storage"; protected $defaults=array("Id"=>null,"Name"=>"","Path"=>""); public static function find_one($p=array(),$o=array()) { $s=new Storage(); $s->Id=$p["Id"]; return $s; } }');
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

// Constants Monitor.php reads.
foreach (array('ZM_OPT_USE_AUTH'=>false, 'ZM_RAND_STREAM'=>false, 'ZM_MIN_STREAMING_PORT'=>0,
    'ZM_WEB_VIDEO_MAXFPS'=>10, 'ZM_WEB_STREAM_METHOD'=>'jpeg', 'ZM_WEB_VIDEO_BITRATE'=>0,
    'ZM_MPEG_LIVE_FORMAT'=>'', 'ZM_RTSP2WEB_PATH'=>'', 'ZM_GO2RTC_PATH'=>'') as $name => $value) {
  if (!defined($name)) define($name, $value);
}

// Global helpers getStreamHTML() reaches for.
function translate($s) { return $s; }
function reScale($dimension, $scale) { return (int)(($dimension * $scale) / 100); }
function editableMonitor($id) { return true; }
function visibleMonitor($id) { return true; }
function canStream() { return true; }
function validInt($v) { return (int)$v; }
function validJsStr($v) { return (string)$v; }
function generateAuthHash($a) { return ''; }
function getImageStreamHTML($id, $src, $width, $height, $title = '') { return '<img id="'.$id.'" src="'.$src.'">'; }
function getImageStill($id, $src, $width, $height, $title = '') { return '<img id="'.$id.'" src="'.$src.'">'; }
function getVideoStreamHTML($id, $src, $width, $height, $format, $title = '') { return '<video id="'.$id.'" src="'.$src.'"></video>'; }
function getWebSiteUrl($id, $src, $width, $height, $title = '') { return '<iframe id="'.$id.'" src="'.$src.'"></iframe>'; }

// Globals getStreamHTML() declares global.
$basename = '';
$view = '';
$user = null;

require_once($root.'/web/includes/Object.php');
require_once($monitor_php);

// Run a call, turning a throw into a string so a DivisionByZeroError is a FAIL.
function call($monitor, $method, $args) {
  try {
    return $monitor->$method($args);
  } catch (\Throwable $e) {
    return 'THREW: '.get_class($e).': '.$e->getMessage();
  }
}

$zero = new ZM\Monitor(array('Id'=>1, 'Width'=>0, 'Height'=>0, 'ServerId'=>1, 'Orientation'=>'ROTATE_0'));
$ok = new ZM\Monitor(array('Id'=>2, 'Width'=>640, 'Height'=>480, 'ServerId'=>1, 'Orientation'=>'ROTATE_0'));

// --- getStreamSrc on a monitor whose size is not known -----------------------
$src = call($zero, 'getStreamSrc', array('width'=>320));
check('zero-size monitor getStreamSrc(width=320) does not throw',
  (is_string($src) and strpos($src, 'THREW:') !== 0) ? true : $src, true);
check('zero-size monitor getStreamSrc(width=320) defaults scale to 100',
  is_string($src) and strpos($src, 'scale=100') !== false, true);

$src = call($zero, 'getStreamSrc', array('height'=>240));
check('zero-size monitor getStreamSrc(height=240) does not throw',
  (is_string($src) and strpos($src, 'THREW:') !== 0) ? true : $src, true);
check('zero-size monitor getStreamSrc(height=240) defaults scale to 100',
  is_string($src) and strpos($src, 'scale=100') !== false, true);

// --- control: a sized monitor still computes the scale ----------------------
$src = call($ok, 'getStreamSrc', array('width'=>320));
check('640x480 monitor getStreamSrc(width=320) does not throw',
  (is_string($src) and strpos($src, 'THREW:') !== 0) ? true : $src, true);
check('640x480 monitor getStreamSrc(width=320) computes scale=50',
  is_string($src) and strpos($src, 'scale=50') !== false, true);

// --- getStreamHTML with a px width on a zero-size monitor -------------------
$html = call($zero, 'getStreamHTML', array('width'=>'640px'));
check('zero-size monitor getStreamHTML(width=640px) does not throw',
  (is_string($html) and strpos($html, 'THREW:') !== 0) ? true : $html, true);
check('zero-size monitor getStreamHTML(width=640px) renders the stream at scale 100',
  is_string($html) and strpos($html, 'imageFeed') !== false and strpos($html, 'scale=100') !== false, true);

$html = call($ok, 'getStreamHTML', array('width'=>'320px'));
check('640x480 monitor getStreamHTML(width=320px) computes scale=50',
  is_string($html) and strpos($html, 'THREW:') !== 0 and strpos($html, 'scale=50') !== false, true);

@unlink($stub_dir.'/database.php');
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Zone', 'Server', 'Storage') as $c) @unlink("$stub_dir/$c.php");
@rmdir($stub_dir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
