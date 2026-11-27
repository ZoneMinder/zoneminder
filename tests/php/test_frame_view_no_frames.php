<?php
// The frame view's no-frames fallback: when the max-score Frames query
// returns false (event has no Frames rows, or they were purged), frame.php
// must not build a Frame from false or pass false to getImageSrc.
// Instead it falls back to the first frame of the event (FrameId 1).
//
// Run as: php tests/php/test_frame_view_no_frames.php
//   ZM_FRAME_VIEW_SRC=<other frame.php> php tests/php/test_frame_view_no_frames.php  # run against another copy

namespace ZM {
  class Frame {
    private $data;
    public function __construct($f) { $this->data = is_array($f) ? $f : array(); }
    public function FrameId() { return isset($this->data['FrameId']) ? (int)$this->data['FrameId'] : 0; }
    public function Score() { return isset($this->data['Score']) ? (int)$this->data['Score'] : 0; }
    public function Type() { return isset($this->data['Type']) ? $this->data['Type'] : 'Normal'; }
  }
  class Event {
    public function __construct($eid) {}
    public function Width() { return 1920; }
    public function MaxScore() { return 99; }
    public function getImageSrc($frame, $scale=0, $captureOnly=false) {
      // Mirrors web/includes/Event.php::getImageSrc, which reads
      // $frame['FrameId'] (and $frame['Delta']) when a frame is given:
      // a false row raises "Trying to access array offset on false".
      return array('FrameId' => $frame['FrameId']);
    }
  }
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

  $stubResult = false;
  function dbFetchOne($sql, $col, $args) {
    global $stubResult;
    if (strpos($sql, 'Score=?') !== false) return $stubResult;
    return null;
  }
  function collect_warning($errno, $errstr, $errfile, $errline) {
    global $warnings;
    $warnings[] = "[$errno] $errstr ($errfile:$errline)";
    return true;
  }

  $framePath = getenv('ZM_FRAME_VIEW_SRC') ?: __DIR__.'/../../web/skins/classic/views/frame.php';
  $src = file_get_contents($framePath);
  if (!$src) { echo "FAIL - cannot read $framePath\n"; exit(1); }
  $start = strpos($src, "if (!empty(\$fid)) {");
  $end = strpos($src, '$imageData = $Event->getImageSrc($frame, $scale, 0);');
  if ($start === false || $end === false) {
    echo "FAIL - could not find the frame-selection code in $framePath\n";
    exit(1);
  }
  $end += strlen('$imageData = $Event->getImageSrc($frame, $scale, 0);') + 1;
  $chunk = substr($src, $start, $end - $start);
  $chunk = preg_replace('/^function frameNavigation.*?\n\}\n/ms', '', $chunk);
  $chunk = preg_replace('/\$nav = frameNavigation\(.*?\);\n/', '', $chunk);

  function run($dbResult) {
    global $chunk, $warnings, $stubResult;
    $warnings = array();
    $stubResult = $dbResult;
    $eid = 7;
    $fid = 0;
    $scale = 0;
    $scales = array('0'=>'Auto', '100'=>'Actual', 'fit_to_width'=>'Fit', '480px'=>'480');
    $Event = new \ZM\Event($eid);
    $Monitor = new class { public function Id() { return 1; } public function DefaultScale() { return 100; } };
    set_error_handler('collect_warning');
    error_reporting(E_ALL);
    eval($chunk);
    restore_error_handler();
    return array('FrameId' => $Frame->FrameId(), 'fid' => $fid, 'warnings' => $warnings);
  }

  echo "no Frames row (dbFetchOne returns false):\n";
  $r = run(false);
  check_warnings('no PHP warnings', $r['warnings']);
  check('chosen FrameId is 1', $r['FrameId'], 1);
  check('fid advanced from 0 to FrameId', $r['fid'], 1);

  echo "normal case (a Frames row is returned):\n";
  $r = run(array('EventId'=>7, 'FrameId'=>5, 'Type'=>'Alarm', 'Score'=>99));
  check_warnings('no PHP warnings', $r['warnings']);
  check('chosen FrameId is the row\'s FrameId', $r['FrameId'], 5);
  check('fid equals the row\'s FrameId', $r['fid'], 5);

  exit($failures ? 1 : 0);
}
