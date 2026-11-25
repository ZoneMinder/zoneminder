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

  // The view requires includes/Event.php and includes/ZonePreset.php, which pull in
  // the real database layer; give it stubs from a temporary include path instead.
  $stubDir = sys_get_temp_dir().'/zm-zone-view-'.getmypid();
  @mkdir($stubDir.'/includes', 0700, true);
  file_put_contents($stubDir.'/includes/Event.php', "<?php\n");
  file_put_contents($stubDir.'/includes/ZonePreset.php', "<?php\nnamespace ZM;\nclass ZonePreset {\n  const BUILT_IN_PRESETS = array();\n  public static function find(\$a = array(), \$b = array()) { return array(); }\n}\n");
  set_include_path($stubDir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubDir) {
    @unlink($stubDir.'/includes/Event.php');
    @unlink($stubDir.'/includes/ZonePreset.php');
    @rmdir($stubDir.'/includes');
    @rmdir($stubDir);
  });

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
