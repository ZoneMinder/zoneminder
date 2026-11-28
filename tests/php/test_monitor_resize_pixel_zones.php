<?php
// Zones whose coordinates have no decimal point are pixels to zmc. Rotating or
// resizing the monitor must keep them covering the same part of the picture
// and keep their pixel thresholds meaning the same share of the zone.
//
// php tests/php/test_monitor_resize_pixel_zones.php [root]
//
// Runs the real web/includes/actions/monitor.php save action against stubbed
// Monitor, Zone and database layers:
//
// Zone coordinates and thresholds are percentages (Units=Percent, and the
//     daemon reads them that way whenever the coordinates have a decimal
//     point), so changing a monitor's Width or Height must not multiply them
//     by the change in area. A 4x larger picture turned a 75 percent maximum
//     alarm area into 300.
//
// php tests/php/test_monitor_save_action.php

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}
  function AuditAction() {}
  function redactForLog($a) { return $a; }

  class Rec {
    public static $saved = array();
    public static $inserted = array();
    public static $queries = array();
    public static $zones = array();
    public static $db = array();
  }

  class Storage {
    public static function find() { return array(); }
    public function Path() { return sys_get_temp_dir().'/zm_test_storage'; }
    public function Name() { return 'Default'; }
  }

  class Monitor {
    private $f = array();
    public function __construct($id = null) {
      if ($id and isset(Rec::$db[$id])) $this->f = Rec::$db[$id];
    }
    public function __call($name, $args) {
      if (count($args)) $this->f[$name] = $args[0];
      return isset($this->f[$name]) ? $this->f[$name] : null;
    }
    public function Id($v = null) { return isset($this->f['Id']) ? $this->f['Id'] : null; }
    public function Storage() { return new Storage(); }
    public function zmcControl($m) {}
    public function zmtrackControl($m) {}
    public function sendControlCommand($m) {}
    public function commandFieldChangesNotAllowed($d) { return array(); }
    public function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if (!array_key_exists($k, $this->f) or (string)$this->f[$k] !== (string)$v) $c[$k] = $v;
      }
      return $c;
    }
    public function save($changes) {
      Rec::$saved[] = $changes;
      foreach ($changes as $k => $v) $this->f[$k] = $v;
      return true;
    }
    public function insert($changes) {
      Rec::$inserted[] = $changes;
      $this->f = $changes + array('Id' => 99);
      return true;
    }
    public function get_last_error() { return ''; }
  }

  class Zone {
    public function save($d) { return true; }
    public function get_last_error() { return ''; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) { return true; }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validDevicePath($v) { return $v; }
  function validHtmlStr($v) { return $v; }
  function dbFetchOne($sql, $col = null, $args = array()) {
    return (strpos($sql, 'MAX(Sequence)') !== false) ? 7 : null;
  }
  function dbFetchAll($sql, $col = null, $args = array()) {
    return (strpos($sql, 'FROM Zones') !== false) ? ZM\Rec::$zones : array();
  }
  function dbQuery($sql, $args = array()) {
    if (strpos($sql, 'UPDATE Zones') === 0) ZM\Rec::$queries[] = array($sql, $args);
    return true;
  }
  function dbLastError() { return ''; }
  function getFormChanges($old, $new, $types = false) {
    $c = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $c[$k] = "`$k` = '$v'";
    }
    return $c;
  }

  foreach (array('coordsToPoints', 'pointsToCoords', 'mapCoords', 'getPolyArea', 'convertPixelPointsToPercent', 'convertZoneThresholdsToPercent') as $fn) {
    if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $src0 = file_get_contents($root.'/web/includes/functions.php'), $mm)) { echo "missing $fn\n"; exit(1); }
    eval($mm[0]);
  }

  // The action takes its checkbox defaults from this helper where it exists.
  $src = file_get_contents($root.'/web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  // The action pulls its classes in with relative require_once calls.
  $dir = sys_get_temp_dir().'/zm_test_action_'.getmypid();
  mkdir($dir.'/includes', 0755, true);
  mkdir(sys_get_temp_dir().'/zm_test_storage', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model') as $f) {
    file_put_contents("$dir/includes/$f.php", "<?php\n");
  }
  chdir($dir);

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

  class FakeUser { public function unviewableMonitorIds() { return array(); } }

  function save($request, $db = array(), $zones = array()) {
    global $error_message, $user, $action, $redirect;
    ZM\Rec::$saved = ZM\Rec::$inserted = ZM\Rec::$queries = array();
    ZM\Rec::$db = $db;
    ZM\Rec::$zones = $zones;
    $_REQUEST = $request;
    $error_message = '';
    $redirect = null;
    $user = new FakeUser();
    $action = 'save';
    ob_start();
    include $GLOBALS['root'].'/web/includes/actions/monitor.php';
    ob_end_clean();
    return $error_message;
  }

  $form = array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Door', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480',
  );
  $existing = array('Id' => 5, 'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Deleted' => 0,
    'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0);
  // Left half of a 640x480 picture, in pixels, 100 pixel minimum alarm area
  $zone = array('Id' => 3, 'Name' => 'Half', 'Units' => 'Pixels',
    'Coords' => '0,0 320,0 320,480 0,480',
    'MinAlarmPixels' => 1536, 'MaxAlarmPixels' => 15360, 'MinFilterPixels' => null,
    'MaxFilterPixels' => null, 'MinBlobPixels' => null, 'MaxBlobPixels' => null);

  function final_zone($zone) {
    foreach (ZM\Rec::$queries as $q) {
      foreach (array('Coords', 'MinAlarmPixels', 'MaxAlarmPixels') as $f) {
        if (preg_match("/`$f` = '([^']*)'/", $q[0], $m)) $zone[$f] = $m[1];
      }
    }
    return $zone;
  }

  // Rotation 640x480 -> 480x640
  save(array('mid' => '5', 'newMonitor' => array('Width' => '480', 'Height' => '640') + $form), array(5 => $existing), array($zone));
  $z = final_zone($zone);
  check('rotated pixel zone is stored as percentages', $z['Coords'], '0.00,0.00 0.00,50.00 100.00,50.00 100.00,0.00');
  check('rotated pixel zone MinAlarmPixels is a percentage of the zone', (float)$z['MinAlarmPixels'], 1.0);
  check('rotated pixel zone MaxAlarmPixels is a percentage of the zone', (float)$z['MaxAlarmPixels'], 10.0);

  // Resize 640x480 -> 1280x960
  save(array('mid' => '5', 'newMonitor' => array('Width' => '1280', 'Height' => '960') + $form), array(5 => $existing), array($zone));
  $z = final_zone($zone);
  check('resized pixel zone keeps covering the left half', $z['Coords'], '0.00,0.00 50.00,0.00 50.00,100.00 0.00,100.00');
  check('resized pixel zone MaxAlarmPixels stays 10 percent of the zone', (float)$z['MaxAlarmPixels'], 10.0);

  system('rm -rf '.escapeshellarg($dir).' '.escapeshellarg(sys_get_temp_dir().'/zm_test_storage'));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
