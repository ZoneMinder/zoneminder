<?php
// Runs the real web/includes/actions/monitor.php save action against stubbed
// Monitor, Zone and database layers.
//
// An Ffmpeg monitor whose Source Path has no scheme (192.168.1.20/live) is
// accepted today, then zmc fails in avformat_open_input with "Protocol not
// found" and zmdc restarts it forever. The save must be refused so the error
// points at the Path field instead of a daemon log.
//
// php tests/php/test_monitor_ffmpeg_path_scheme.php

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
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) { return true; }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validDevicePath($v) { return $v; }
  function validHtmlStr($v) { return $v; }
  function translate($s) { return $s; }
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
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
  function getFormChanges($old, $new, $types = false) {
    $c = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $c[$k] = "`$k` = '$v'";
    }
    return $c;
  }

  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  $dir = sys_get_temp_dir().'/zm_test_ffmpeg_'.getmypid();
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
    include __DIR__.'/../../web/includes/actions/monitor.php';
    ob_end_clean();
    return $error_message;
  }

  function saves_anything() {
    return count(ZM\Rec::$saved) + count(ZM\Rec::$inserted);
  }

  $form = array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Front', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480',
  );
  $existing = array('Id' => 5, 'Name' => 'Front', 'Type' => 'Ffmpeg', 'Deleted' => 0,
    'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0);

  // Refused: a bare host/path or userinfo@host/path has no protocol.
  foreach (array('192.168.1.20/live', 'admin:pw@cam/stream') as $bad) {
    $err = save(array('newMonitor' => $form + array('Path' => $bad)));
    check("refuses Ffmpeg path without a protocol: $bad", $err !== '', true);
    check("does not save Ffmpeg path without a protocol: $bad", saves_anything(), 0);
  }

  // Accepted: real URL schemes.
  foreach (array('rtsp://192.168.1.20/live', 'http://192.168.1.20/live',
      'https://192.168.1.20/live') as $good) {
    $err = save(array('newMonitor' => $form + array('Path' => $good)));
    check("accepts Ffmpeg path: $good", $err, '');
    check("saves Ffmpeg path: $good", saves_anything(), 1);
  }

  // Accepted: forms ffmpeg handles without a scheme (absolute paths, v4l2:).
  foreach (array('/dev/video0', '/path/file.mp4', 'v4l2:/dev/video0') as $good) {
    $err = save(array('newMonitor' => $form + array('Path' => $good)));
    check("accepts scheme-less form ffmpeg handles: $good", $err, '');
  }

  // An existing Ffmpeg monitor with no submitted Path is checked against the
  // stored Path.
  $err = save(array('mid' => '5', 'newMonitor' => $form),
    array(5 => $existing + array('Path' => '192.168.1.20/live')));
  check('refuses the stored path without a protocol when Path is not submitted', $err !== '', true);
  check('does not save the stored path without a protocol', saves_anything(), 0);

  $err = save(array('mid' => '5', 'newMonitor' => $form),
    array(5 => $existing + array('Path' => 'rtsp://192.168.1.20/live')));
  check('accepts the stored path with a protocol', $err, '');

  // Other monitor types keep their legacy Path values.
  $err = save(array('newMonitor' => array('Type' => 'Remote', 'Path' => '192.168.1.20/live') + $form));
  check('a non-Ffmpeg type is unaffected', $err, '');
  check('a non-Ffmpeg monitor still saves', saves_anything(), 1);

  system('rm -rf '.escapeshellarg($dir).' '.escapeshellarg(sys_get_temp_dir().'/zm_test_storage'));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
