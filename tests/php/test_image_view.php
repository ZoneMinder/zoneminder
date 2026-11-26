<?php
// Tests web/views/image.php and web/views/view_video.php by including the real
// views against stubbed Event/Frame classes and a fake ffmpeg script.
// Each scenario runs in a child php process because the views declare
// functions and call exit().
//
// Run: php tests/php/test_image_view.php   (from the repo root)

$root = realpath(__DIR__.'/../..');
// Optional: php test_image_view.php <dir containing web/> to test another tree.
if (PHP_SAPI !== 'cli-server' && isset($argv[1])) putenv('ZM_TEST_ROOT='.realpath($argv[1]));
if (getenv('ZM_TEST_ROOT')) $root = getenv('ZM_TEST_ROOT');

if (PHP_SAPI === 'cli-server') {
  // ---- router: run one request against one view ----
  $cfgFile = $_GET['cfg'];
  $cfg = json_decode(file_get_contents($cfgFile), true);
  $GLOBALS['ZMT'] = $cfg;
  $_REQUEST = $cfg['request'];
  set_include_path($cfg['stubdir'].PATH_SEPARATOR.$root.'/web'.PATH_SEPARATOR.$root.'/web/includes');
  define('ZM_EVENT_IMAGE_DIGITS', 5);
  define('ZM_PATH_FFMPEG', $cfg['ffmpeg']);
  define('ZM_ENABLE_CSRF_MAGIC', false);
  set_error_handler(function($no, $str) use ($cfgFile) {
    file_put_contents($cfgFile.'.errors', $str."\n", FILE_APPEND);
    return true;
  });
  function canView($what) { return in_array($what, $GLOBALS['ZMT']['canView']); }
  function canEdit($what) { return false; }
  function validInt($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/[^\-\d]/', '', $input);
  }
  ob_start();
  $view = '';
  include $root.'/web/views/'.$cfg['view'].'.php';
  exit();
}

// ---- parent ----
$passed = 0;
$failed = 0;
function check($label, $ok, $detail = '') {
  global $passed, $failed;
  if ($ok) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label $detail\n";
  }
}

$tmp = sys_get_temp_dir().'/zm_image_view_'.getmypid();
mkdir($tmp, 0700, true);
mkdir($tmp.'/inc/includes', 0700, true);

file_put_contents($tmp.'/inc/includes/Event.php', <<<'EOT'
<?php
namespace ZM;
function Debug($s) {}
function Info($s) {}
function Warning($s) {}
function Error($s) {}
class Event {
  private $d = array();
  public function __construct($id = 0) {
    $e = $GLOBALS['ZMT']['event'];
    if ($id == $e['Id']) $this->d = $e;
  }
  public static function find_one($p) {
    $e = new Event($p['Id']);
    return $e->Id() ? $e : null;
  }
  public function canView() { return true; }
  public function Id() { return isset($this->d['Id']) ? $this->d['Id'] : 0; }
  public function MonitorId() { return 1; }
  public function Path() { return $this->d['Path']; }
  public function SaveJPEGs() { return $this->d['SaveJPEGs']; }
  public function DefaultVideo() { return $this->d['DefaultVideo']; }
  public function MaxScore() { return 0; }
  public function Frames() { return $this->d['Frames']; }
  public function EndDateTime() { return $this->d['EndDateTime']; }
  public function DiskSpace($v) {}
  public function find_virtual_frame($fid) {
    $f = new Frame();
    $f->FrameId($fid);
    $f->Delta($fid * 0.1);
    return $f;
  }
}
EOT);
file_put_contents($tmp.'/inc/includes/Frame.php', <<<'EOT'
<?php
namespace ZM;
class Frame {
  private $d = array('FrameId' => 0, 'Delta' => 0);
  public static function find_one($p, $o = array()) {
    foreach ($GLOBALS['ZMT']['frames'] as $row) {
      $ok = true;
      foreach ($p as $k => $v) if (!isset($row[$k]) or $row[$k] != $v) $ok = false;
      if ($ok) { $f = new Frame(); $f->d = $row; return $f; }
    }
    return null;
  }
  public function FrameId($n = null) { if ($n !== null) $this->d['FrameId'] = $n; return $this->d['FrameId']; }
  public function Delta($n = null) { if ($n !== null) $this->d['Delta'] = $n; return $this->d['Delta']; }
  public function Id($n = null) { if ($n !== null) $this->d['Id'] = $n; return isset($this->d['Id']) ? $this->d['Id'] : 0; }
}
EOT);

// A fake ffmpeg: logs its arguments and writes a small jpeg to the last argument.
$ffmpegLog = $tmp.'/ffmpeg.log';
$srcJpg = $tmp.'/src.jpg';
$im = imagecreatetruecolor(64, 48);
imagejpeg($im, $srcJpg);
file_put_contents($tmp.'/ffmpeg', "#!/bin/sh\nfor a in \"\$@\"; do l=\"\$a\"; done\nprintf '%s\\n' \"\$*\" >> ".escapeshellarg($ffmpegLog)."\ncp ".escapeshellarg($srcJpg)." \"\$l\"\n");
chmod($tmp.'/ffmpeg', 0700);

$port = 20000 + (getmypid() % 20000);
$base = 'http://127.0.0.1:'.$port;
$server = proc_open(array(PHP_BINARY, '-d', 'display_errors=0', '-d', 'log_errors=1', '-d', 'error_log='.$tmp.'/php.log', '-S', '127.0.0.1:'.$port, __FILE__),
  array(0 => array('file', '/dev/null', 'r'), 1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w')), $pipes);
for ($i = 0; $i < 50; $i++) {
  $c = @fsockopen('127.0.0.1', $port);
  if ($c) { fclose($c); break; }
  usleep(100000);
}

$scn = 0;
function run_view($view, $request, $eventOver = array(), $files = array(), $opts = array()) {
  global $tmp, $scn, $ffmpegLog, $srcJpg;
  $scn++;
  $dir = $tmp.'/ev'.$scn;
  mkdir($dir, 0700, true);
  foreach ($files as $name => $content) {
    file_put_contents($dir.'/'.$name, $content === true ? file_get_contents($srcJpg) : $content);
  }
  @unlink($ffmpegLog);
  $event = array_merge(array(
    'Id' => 5, 'Path' => $dir, 'SaveJPEGs' => 0, 'DefaultVideo' => '',
    'Frames' => 30, 'EndDateTime' => '2026-01-01 00:00:00',
  ), $eventOver);
  $cfg = array(
    'view' => $view, 'request' => $request, 'event' => $event,
    'frames' => isset($opts['frames']) ? $opts['frames'] : array(),
    'canView' => isset($opts['canView']) ? $opts['canView'] : array('Events', 'Snapshots'),
    'stubdir' => $tmp.'/inc', 'ffmpeg' => $tmp.'/ffmpeg',
  );
  file_put_contents($tmp.'/cfg'.$scn.'.json', json_encode($cfg));
  $errFile = $tmp.'/cfg'.$scn.'.json.errors';
  @unlink($errFile);
  $ctx = stream_context_create(array('http' => array('ignore_errors' => true, 'timeout' => 20)));
  $body = @file_get_contents($GLOBALS['base'].'/?cfg='.urlencode($tmp.'/cfg'.$scn.'.json'), false, $ctx);
  $res = array('headers' => isset($http_response_header) ? $http_response_header : array(), 'status' => 0);
  if ($res['headers'] && preg_match('@^HTTP/\S+ (\d+)@', $res['headers'][0], $m)) $res['status'] = (int)$m[1];
  $res['body'] = (string)$body;
  $res['errors'] = file_exists($errFile) ? array_values(array_filter(explode("\n", file_get_contents($errFile)))) : array();
  $res['fatal'] = $res['status'] >= 500;
  $res['raw'] = $res['body'];
  $res['dir'] = $dir;
  $res['ffmpeg'] = file_exists($ffmpegLog) ? trim(file_get_contents($ffmpegLog)) : '';
  return $res;
}
function header_of($res, $name) {
  foreach ($res['headers'] as $h) {
    if (stripos($h, $name.':') === 0) return trim(substr($h, strlen($name) + 1));
  }
  return null;
}

echo "control: stored frame is served as is\n";
$r = run_view('image', array('eid' => 5, 'fid' => 3), array(), array('00003-capture.jpg' => true),
  array('frames' => array(array('EventId' => 5, 'FrameId' => 3, 'Delta' => 0.3))));
check("image/jpeg content type", header_of($r, "Content-type") === "image/jpeg", json_encode($r["headers"]));
check('bytes are the stored jpeg', $r['body'] === file_get_contents($srcJpg));

echo "control: width and height scale the image\n";
$r = run_view('image', array('eid' => 5, 'fid' => 3, 'width' => 32, 'height' => 24), array(), array('00003-capture.jpg' => true),
  array('frames' => array(array('EventId' => 5, 'FrameId' => 3, 'Delta' => 0.3))));
$size = @getimagesizefromstring($r['body']);
check('scaled to 32x24', $size && $size[0] == 32 && $size[1] == 24);

echo "view_video.php on an event with no video file\n";
// DefaultVideo is empty, so the path is the event directory itself. fopen()
// succeeds on a directory, so the view answered 200 with Content-Length 4096
// and no body instead of 404.
$r = run_view('view_video', array('eid' => 5));
check('answers 404', $r['status'] === 404, 'status='.var_export($r['status'], true));
check('does not announce a 4096 byte body', header_of($r, 'Content-Length') === null);
check('no PHP warnings', $r['errors'] === array(), json_encode($r['errors']));

echo "image.php Content-Disposition for objdetect gif and mp4\n";
foreach (array('objdetect_gif' => 'gif', 'objdetect_mp4' => 'mp4') as $fid => $ext) {
  $r = run_view('image', array('eid' => 5, 'fid' => $fid), array(), array('objdetect.'.$ext => 'GIF89a-not-really-an-image'));
  $cd = (string)header_of($r, 'Content-Disposition');
  check("$fid file name ends in .$ext", preg_match('/_objdetect\.'.$ext.'"$/', $cd) === 1, $cd);
}

echo "image.php scale on a jpeg that cannot be decoded\n";
// A zero length jpeg (zmc has created the file but not written it yet).
$r = run_view('image', array('eid' => 5, 'fid' => 3, 'scale' => 50), array(), array('00003-capture.jpg' => ''),
  array('frames' => array(array('EventId' => 5, 'FrameId' => 3, 'Delta' => 0.3))));
check('no fatal error', !$r['fatal'], 'status='.$r['status'].' '.@file_get_contents($tmp.'/php.log'));

echo "image.php fid past the last frame of a closed event\n";
$ev = array('DefaultVideo' => 'video.mp4', 'Frames' => 30);
$r = run_view('image', array('eid' => 5, 'fid' => 400), $ev, array('video.mp4' => 'x'));
check('answers 404', $r['status'] === 404, 'status='.var_export($r['status'], true));
check('does not run ffmpeg', $r['ffmpeg'] === '', $r['ffmpeg']);
check('does not create an image file', !file_exists($r['dir'].'/00400-capture.jpg'));
$r = run_view('image', array('eid' => 5, 'fid' => 25), $ev, array('video.mp4' => 'x'));
check('last-frames-in-range fid is still extracted', file_exists($r['dir'].'/00025-capture.jpg') && $r['ffmpeg'] !== '');
$r = run_view('image', array('eid' => 5, 'fid' => 400), array_merge($ev, array('EndDateTime' => null)), array('video.mp4' => 'x'));
check('open event (Frames not final) is not clamped', file_exists($r['dir'].'/00400-capture.jpg'));

echo "image.php fractional width\n";
$r = run_view('image', array('eid' => 5, 'fid' => 3, 'width' => '32.5', 'height' => '24'), array(), array('00003-capture.jpg' => true),
  array('frames' => array(array('EventId' => 5, 'FrameId' => 3, 'Delta' => 0.3))));
check('no deprecation raised', $r['errors'] === array(), json_encode($r['errors']));
check('cache file name uses whole pixels', count(glob($r['dir'].'/00003-capture-32x24.jpg')) === 1, implode(',', array_map('basename', glob($r['dir'].'/*'))));

echo "image.php with no fid and no Events permission\n";
$r = run_view('image', array(), array(), array(), array('canView' => array()));
check('no undefined index warning', $r['errors'] === array(), json_encode($r['errors']));

echo "image.php Cache-Control for snapshot and alarm images\n";
// snapshot.jpg and alarm.jpg are rewritten while the event is recording.
foreach (array('snapshot', 'alarm') as $fid) {
  $r = run_view('image', array('eid' => 5, 'fid' => $fid), array('EndDateTime' => null), array($fid.'.jpg' => true));
  $cc = (string)header_of($r, 'Cache-Control');
  check("$fid of an open event is served", $r['status'] === 200, 'status='.$r['status']);
  check("$fid of an open event is not cached for a day", strpos($cc, 'max-age=86400') === false && $cc !== '', $cc);
  $r = run_view('image', array('eid' => 5, 'fid' => $fid), array(), array($fid.'.jpg' => true));
  check("$fid of a finished event keeps the day long max-age", header_of($r, 'Cache-Control') === 'max-age=86400', (string)header_of($r, 'Cache-Control'));
  $exp = strtotime((string)header_of($r, 'Expires'));
  check("$fid of a finished event: Expires matches max-age", $exp !== false && abs($exp - (time() + 86400)) < 120, (string)header_of($r, 'Expires'));
}

echo "\n$passed passed, $failed failed\n";
proc_terminate($server);
exec('rm -rf '.escapeshellarg($tmp));
exit($failed ? 1 : 0);
