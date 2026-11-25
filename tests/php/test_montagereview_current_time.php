<?php
// Montage review opened with &current= (the event view's Montage Review button)
// must start its slider at that time, not at the start of the window.
//
// views/js/montagereview.js.php is required from inside xhtmlFooter(), so it
// only sees the view's variables it pulls in with `global`.
//
// Run: php tests/php/test_montagereview_current_time.php   (from the repo root)

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

  $passed = 0;
  $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label\n";
      echo "       got:  ".var_export($got, true)."\n";
      echo "       want: ".var_export($want, true)."\n";
    }
  }

  // Same scope as the real include in xhtmlFooter().
  function render_js() {
    ob_start();
    require __DIR__.'/../../web/skins/classic/views/js/montagereview.js.php';
    return ob_get_clean();
  }

  function current_secs($js) {
    return preg_match('/var currentTimeSecs=parseFloat\(([0-9.]+)\);/', $js, $m) ? (int)$m[1] : null;
  }

  // Globals as montagereview.php leaves them for ?view=montagereview&live=0&current=...
  $defaultScale = 1;
  $liveMode = 0;
  $fitMode = 1;
  $speeds = array(1);
  $speedIndex = 0;
  $initialDisplayInterval = 1000;
  $monitors = array();
  $eventsSql = '';
  $defaultCurrentTimeSecs = strtotime('2026-01-01 12:00:00');
  $minTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs - 1800);
  $maxTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs + 1800);

  check('current= sets the slider time', current_secs(render_js()), $defaultCurrentTimeSecs);

  $defaultCurrentTimeSecs = strtotime('not a time');
  check('unparsable current= starts at minTime', current_secs(render_js()), strtotime($minTime));

  unset($defaultCurrentTimeSecs);
  check('no current= starts at minTime', current_secs(render_js()), strtotime($minTime));

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
