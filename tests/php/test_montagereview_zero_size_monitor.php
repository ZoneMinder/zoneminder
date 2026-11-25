<?php
// Montage review must render for a monitor whose Width or Height is still 0
// (size not known yet). montagereview.js.php divided the average monitor area
// by each monitor's area: DivisionByZeroError on PHP 8, and on PHP 7 an INF
// written into the page script, which JavaScript does not know.
//
// Run: php tests/php/test_montagereview_zero_size_monitor.php   (from the repo root)

namespace ZM {
  class Storage {
    public static function find() { return array(); }
    public function Id() { return 0; }
    public function Name($new=null) { return ''; }
    public function ServerId() { return 0; }
    public function to_json() { return '{}'; }
  }
  function Warning($m) {}
}

namespace {
  ini_set('date.timezone', 'UTC');
  define('ZM_MIN_STREAMING_PORT', 0);
  function validJsStr($s) { return (string)$s; }
  function validHtmlStr($s) { return (string)$s; }

  class StubMonitor {
    private $f;
    public function __construct($id, $width, $height) {
      $this->f = array('Id'=>$id, 'Width'=>$width, 'Height'=>$height, 'Name'=>'m'.$id);
    }
    public function Id() { return $this->f['Id']; }
    public function Width() { return $this->f['Width']; }
    public function Height() { return $this->f['Height']; }
    public function ViewWidth() { return $this->f['Width']; }
    public function ViewHeight() { return $this->f['Height']; }
    public function Name() { return $this->f['Name']; }
    public function Deleted() { return false; }
    public function canView() { return true; }
    public function getStreamSrc($args, $sep) { return ''; }
    public function __call($name, $args) { return ''; }
  }

  $passed = 0;
  $failed = 0;
  function check($label, $ok, $detail = '') {
    global $passed, $failed;
    if ($ok) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label".($detail !== '' ? " ($detail)" : '')."\n";
    }
  }

  // Same scope as the real include in xhtmlFooter().
  function render_js() {
    ob_start();
    try {
      require __DIR__.'/../../web/skins/classic/views/js/montagereview.js.php';
    } catch (\Throwable $e) {
      ob_end_clean();
      return get_class($e).': '.$e->getMessage();
    }
    return ob_get_clean();
  }

  // monitor id => the value written for monitorNormalizeScale, as text
  function scales($js) {
    preg_match_all('/monitorNormalizeScale\[(\d+)\]=([^;]*);/', $js, $m);
    return array_combine($m[1], $m[2]);
  }

  function near($text, $want) {
    return is_numeric($text) and abs((float)$text - $want) < 1e-9;
  }

  // Globals as montagereview.php leaves them
  $defaultScale = 1;
  $liveMode = 0;
  $fitMode = 1;
  $speeds = array(1);
  $speedIndex = 0;
  $initialDisplayInterval = 1000;
  $eventsSql = '';
  $minTime = '2026-01-01 11:00:00';
  $maxTime = '2026-01-01 12:00:00';

  $sized = array(new StubMonitor(1, 640, 480), new StubMonitor(2, 1280, 960));
  // average area 768000
  $want = array(1 => sqrt(768000/307200), 2 => sqrt(768000/1228800));

  $monitors = $sized;
  $s = scales(render_js());
  check('sized monitors are normalized to the average area',
    isset($s[1], $s[2]) and near($s[1], $want[1]) and near($s[2], $want[2]), json_encode($s));

  foreach (array('0x0'=>array(0, 0), '640x0'=>array(640, 0), '0x480'=>array(0, 480)) as $label => $size) {
    $monitors = array_merge($sized, array(new StubMonitor(3, $size[0], $size[1])));
    $js = render_js();
    $s = scales($js);
    check("a $label monitor gets a finite scale",
      isset($s[3]) and is_numeric($s[3]) and is_finite((float)$s[3]), isset($s[3]) ? $s[3] : substr($js, 0, 80));
    check("a $label monitor leaves the other scales unchanged",
      isset($s[1], $s[2]) and near($s[1], $want[1]) and near($s[2], $want[2]), json_encode($s));
  }

  $monitors = array(new StubMonitor(4, 0, 0));
  $s = scales(render_js());
  check('only unsized monitors still renders', isset($s[4]) and near($s[4], 1), json_encode($s));

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
