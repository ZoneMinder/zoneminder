<?php
// A failed zone save (update or new zone) leaves $redirect empty, so index.php
// renders the zone view with the action's $zone, which has no Points. The view
// skipped its own setup because $zone was set and died with
// "ksort(): Argument #1 ($array) must be of type array, null given" (HTTP 500,
// blank page) instead of showing the save error on the zone editor.
//
// Runs the real actions/zone.php and then the real views/zone.php, with
// xhtmlHeaders() stubbed to stop the view once its setup is done.
//
// Run as: php tests/php/test_zone_save_failure_view.php [repo-root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($action, $object, $id, $message) {}

  class Zone { const BUILT_IN_PRESETS = array(); }
  class ZonePreset {
    const BUILT_IN_PRESETS = array();
    public static function find($a = array(), $o = array()) { return array(); }
  }
  class Event {
    public static function find($a = array(), $o = array()) { return array(); }
  }
  class Monitor {
    public function __construct($id = null) {}
    public function Id() { return 5; }
    public function Type() { return 'Ffmpeg'; }
    public function Controllable() { return false; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
    public function zmcControl($command) {}
    public function connKey() { return 1; }
  }
}

namespace {
  class ViewReached extends Exception {}

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
  foreach (array('web/includes/actions/zone.php', 'web/skins/classic/views/zone.php', 'web/includes/functions.php') as $f) {
    if (!is_file("$root/$f")) {
      fwrite(STDERR, "No $f in $root\n");
      exit(2);
    }
  }

  // The zone helpers are taken from functions.php by name, as that file needs the generated config.
  $source = file_get_contents("$root/web/includes/functions.php");
  foreach (array('linesIntersect', 'isSelfIntersecting', 'getPolyArea', 'mapCoords', 'pointsToCoords', 'coordsToPoints', 'limitPoints', 'convertPixelPointsToPercent', 'convertZoneThresholdsToPercent') as $name) {
    if (!preg_match('/^(?:\/\/[^\n]*\n)*function '.$name.'\(.*?^}[^\n]*\n/ms', $source, $m)) {
      echo "FAIL - $name not found in functions.php\n";
      exit(1);
    }
    eval(preg_replace('/^\/\/[^\n]*\n/m', '', $m[0]));
  }

  $stubDir = sys_get_temp_dir().'/zm_zone_view_stubs_'.getmypid();
  @mkdir($stubDir.'/includes', 0700, true);
  foreach (array('Zone', 'ZonePreset', 'Event', 'Monitor') as $f) {
    file_put_contents($stubDir."/includes/$f.php", "<?php\n");
  }
  set_include_path($stubDir.PATH_SEPARATOR.get_include_path());

  class StubUser { public function Id() { return 2; } }

  $dbFailed = false;
  function canEdit($area, $id = null) { return true; }
  function validInt($v) { return (int)$v; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v); }
  function translate($s) { return $s; }
  function daemonCheck() { return false; }
  function localRedirectUrl($referer, $default) { return $referer ? $referer : $default; }
  function getFormChanges($old, $new, $types = array()) {
    $changes = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $changes[] = "`$k` = '".addslashes($v)."'";
    }
    return $changes;
  }
  function dbFetchOne($sql, $col = null, $params = null) {
    return array('Id' => 7, 'MonitorId' => 5, 'Name' => 'Front', 'Type' => 'Active', 'Units' => 'Percent',
      'Coords' => '0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00', 'NumCoords' => 4, 'Area' => 10000,
      'MinAlarmPixels' => '', 'MaxAlarmPixels' => '', 'MinFilterPixels' => '', 'MaxFilterPixels' => '',
      'MinBlobPixels' => '', 'MaxBlobPixels' => '');
  }
  function dbFetchAll($sql, $col = null, $params = null) { return array(); }
  function getEnumValues($t, $c) { return array(); }
  function dbQuery($sql, $params = null, $debug = false) { return null; }
  function dbLastError() { return 'Data too long for column \'Name\' at row 1'; }
  function xhtmlHeaders($file, $title) { throw new ViewReached(); }

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).', want '.var_export($want, true).")\n";
    }
  }

  // index.php runs the action, then the view, both at global scope.
  function saveThenView($root, $zid) {
    global $error_message, $redirect, $user, $action, $view, $zone, $zid_unused;
    $_REQUEST = array('mid' => '5', 'zid' => (string)$zid,
      'newZone' => array('Name' => str_repeat('x', 65), 'Type' => 'Active'));
    $error_message = '';
    $redirect = null;
    $user = new StubUser();
    $action = 'zone';
    $view = 'zone';
    unset($zone);
    ob_start();
    include "$root/web/includes/actions/zone.php";
    $problem = '';
    if ($redirect === null) {
      $_REQUEST = array('mid' => '5', 'zid' => (string)$zid);
      set_error_handler(function($no, $str) use (&$problem) { $problem = $str; return true; });
      try {
        include "$root/web/skins/classic/views/zone.php";
        $problem = $problem ?: 'view ran past its setup';
      } catch (ViewReached $e) {
      } catch (Throwable $e) {
        $problem = $e->getMessage();
      }
      restore_error_handler();
    }
    ob_end_clean();
    return array($redirect, $problem, $view, $error_message, isset($zone['Points']) ? $zone['Points'] : null);
  }

  foreach (array('update zone' => 7, 'new zone' => 0) as $label => $zid) {
    echo "failed save, $label\n";
    list($redirect, $problem, $viewName, $message, $points) = saveThenView($root, $zid);
    check('no redirect', $redirect, null);
    check('view sets up without an error', $problem, '');
    check('view is not replaced by the error view', $viewName, 'zone');
    check('save error is kept for the page', strpos($message, 'Error saving zone') !== false, true);
    check('zone has points to draw', is_array($points) && count($points) >= 3, true);
  }

  foreach (array('Zone', 'ZonePreset', 'Event', 'Monitor') as $f) @unlink($stubDir."/includes/$f.php");
  @rmdir($stubDir.'/includes');
  @rmdir($stubDir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
