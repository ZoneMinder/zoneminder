<?php
// views/zone.php with a zid that is not a zone of the monitor (a deleted zone's
// bookmark, or another monitor's zone) carried on with $zone === false: a few
// dozen "Undefined array key" warnings, getPolyArea() on an empty point list and
// a zone editor script that is a syntax error. It shows the error view now.
//
// Run: php tests/php/test_zone_view_missing_zone.php   (from the repo root)

namespace ZM {
  class Monitor {
    public function __construct($id = 0) {}
    public function Id() { return 7; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
  }
}

namespace {
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

  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function canEdit($a, $id = null) { return true; }
  function translate($s) { return $s; }
  function dbFetchAll($sql) { return array(); }
  function getEnumValues($t, $c) { return array(); }
  $zoneRow = false;
  function dbFetchOne($sql, $col = null, $params = array()) { return $GLOBALS['zoneRow']; }

  function run_view($zid) {
    $_REQUEST = array('mid' => '7', 'zid' => $zid);
    $view = 'zone';
    $warnings = array();
    set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
    try {
      require __DIR__.'/../../web/skins/classic/views/zone.php';
    } catch (Throwable $e) {
      $warnings[] = $e->getMessage();
    }
    restore_error_handler();
    return array($view, $warnings);
  }

  list($view, $warnings) = run_view('99');
  check('zone that is not found shows the error view', $view, 'error');
  check('zone that is not found raises no warnings', $warnings, array());

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
