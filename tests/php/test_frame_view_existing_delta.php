<?php
// The frame view's frame selection: a Frames row that exists must keep its own
// Delta, because that is the -ss offset used when the image has to be pulled
// from the event video. The neighbour-interpolated virtual frame is only for a
// missing row. Also, when getImageSrc() returns '' the fallback $imageData must
// carry imageClass, since the template reads it for the img class.
//
// Run as: php tests/php/test_frame_view_existing_delta.php [path/to/frame.php]

namespace ZM {
  class Frame {
    private $data;
    public function __construct($f) { $this->data = is_array($f) ? $f : array(); }
    public function FrameId() { return isset($this->data['FrameId']) ? (int)$this->data['FrameId'] : 0; }
    public function Type() { return isset($this->data['Type']) ? $this->data['Type'] : 'Normal'; }
  }
  class VirtualFrame {
    private $delta;
    public function __construct($d) { $this->delta = $d; }
    public function Delta() { return $this->delta; }
  }
  class Event {
    public $virtualCalls = array();
    public $imageSrcResult = null;
    public function __construct($eid) {}
    public function Width() { return 1920; }
    public function find_virtual_frame($fid) {
      $this->virtualCalls[] = $fid;
      return new VirtualFrame(4.5);
    }
    public function getImageSrc($frame, $scale=0, $captureOnly=false) {
      return $this->imageSrcResult;
    }
  }
  function Error($msg) {}
}

namespace {
  $failures = 0;
  $warnings = array();
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "  ok $name\n"; return; }
    $failures++;
    echo "  FAIL $name\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
  }
  function check_warnings($name, $warnings) {
    global $failures;
    if (!$warnings) { echo "  ok $name\n"; return; }
    $failures++;
    echo "  FAIL $name\n    warnings: ".var_export($warnings, true)."\n";
  }
  define('SCALE_BASE', 100);
  function translate($s) { return $s; }
  function validInt($v) { return (int)$v; }
  function validNum($v) { return (float)$v; }

  $stubRow = false;
  function dbFetchOne($sql, $col, $args) {
    global $stubRow;
    if (strpos($sql, 'FrameId=?') !== false) return $stubRow;
    return null;
  }
  function collect_warning($errno, $errstr, $errfile, $errline) {
    global $warnings;
    $warnings[] = "[$errno] $errstr ($errfile:$errline)";
    return true;
  }

  $framePath = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views/frame.php';
  $src = @file_get_contents($framePath);
  if (!$src) { echo "FAIL - cannot read $framePath\n"; exit(1); }
  $start = strpos($src, "if (!empty(\$fid)) {");
  $end = strpos($src, "\$show = 'capture';");
  if ($start === false || $end === false) {
    echo "FAIL - could not find the frame-selection code in $framePath\n";
    exit(1);
  }
  $chunk = substr($src, $start, $end - $start);
  $chunk = preg_replace('/^function frameNavigation.*?\n\}\n/ms', '', $chunk);
  $chunk = preg_replace('/\$nav = frameNavigation\(.*?\);\n/', '', $chunk);

  // Mirrors the array web/includes/Event.php::getImageSrc returns for a frame.
  $normalImageData = array(
    'hasAnalysisImage' => 0, 'thumbPath' => '', 'eventPath' => '',
    'imageClass' => 'normal',
  );

  function run($dbResult, $fid, $imageSrcResult) {
    global $chunk, $warnings, $stubRow;
    $warnings = array();
    $stubRow = $dbResult;
    $eid = 7;
    $scale = 0;
    $scales = array('0'=>'Auto', '100'=>'Actual', 'fit_to_width'=>'Fit', '480px'=>'480');
    $Event = new \ZM\Event($eid);
    $Event->imageSrcResult = $imageSrcResult;
    $Monitor = new class { public function Id() { return 1; } public function DefaultScale() { return 100; } };
    set_error_handler('collect_warning');
    error_reporting(E_ALL);
    eval($chunk);
    // frame.php line 148 reads $imageData['imageClass'] for the img class.
    $imageClass = $imageData['imageClass'];
    restore_error_handler();
    return array(
      'delta' => isset($frame['Delta']) ? $frame['Delta'] : null,
      'virtualCalls' => $Event->virtualCalls,
      'imageClass' => $imageClass,
      'warnings' => $warnings,
    );
  }

  echo "an existing Frames row keeps its own Delta:\n";
  $r = run(array('EventId'=>7, 'FrameId'=>3, 'Type'=>'Alarm', 'Score'=>50, 'Delta'=>12.5), 3, $normalImageData);
  check_warnings('no PHP warnings', $r['warnings']);
  check('Delta stays 12.5', $r['delta'], 12.5);
  check('find_virtual_frame not called', $r['virtualCalls'], array());

  echo "a missing Frames row uses the virtual frame Delta:\n";
  $r = run(false, 4, $normalImageData);
  check_warnings('no PHP warnings', $r['warnings']);
  check('Delta comes from the virtual frame', $r['delta'], 4.5);
  check('find_virtual_frame called with fid', $r['virtualCalls'], array(4));

  echo "getImageSrc() returns '' (fallback imageData):\n";
  $r = run(array('EventId'=>7, 'FrameId'=>3, 'Type'=>'Normal', 'Score'=>0, 'Delta'=>1.0), 3, '');
  check_warnings('no PHP warnings reading imageClass', $r['warnings']);
  check('fallback imageClass defaults to normal', $r['imageClass'], 'normal');

  exit($failures ? 1 : 0);
}
