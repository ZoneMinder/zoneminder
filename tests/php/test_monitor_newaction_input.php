<?php
// Runs the real web/includes/actions/monitor.php newAction handler against
// stubbed Monitor/MonitorAction/CronTab and database layers.
//
// The handler reads $_REQUEST['newAction'] rows straight from the browser, so
// every scalar it touches may arrive as an array (or the row itself may not
// even be an array). None of that may reach a string function such as trim()
// or validInt(), which raise TypeError on PHP 8.
//
// php tests/php/test_monitor_newaction_input.php [path-to-monitor.php]

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
    // Values handed to MonitorAction::insert() / save().
    public static $actionInserts = array();
    public static $actionSaves = array();
  }

  class Storage {
    public static function find() { return array(); }
    public function Path() { return sys_get_temp_dir().'/zm_test_newaction_storage'; }
    public function Name() { return 'Default'; }
  }

  class Monitor {
    private $f = array();
    public function __construct($id = null) {
      if ($id and isset(Rec::$db[$id])) $this->f = Rec::$db[$id];
    }
    public static function find_one($q = array()) {
      $id = isset($q['Id']) ? $q['Id'] : null;
      return new self($id);
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
        if (!array_key_exists($k, $this->f)) {
          $c[$k] = $v;
        } else if (is_array($this->f[$k]) or is_array($v)) {
          if ($this->f[$k] != $v) $c[$k] = $v;
        } else if ((string)$this->f[$k] !== (string)$v) {
          $c[$k] = $v;
        }
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

  class MonitorAction {
    private $f = array();
    public function __construct($id = null) {
      if ($id) $this->f['Id'] = $id;
    }
    public static function find_one($q = array()) {
      return null;
    }
    public static function typesForMonitor($monitor) {
      return array('LightOn', 'LightOff', 'IndicatorLightOn', 'IndicatorLightOff',
        'AudioPlay', 'AudioStop');
    }
    public function Id($v = null) {
      if ($v !== null) $this->f['Id'] = $v;
      return isset($this->f['Id']) ? $this->f['Id'] : 0;
    }
    public function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if (!array_key_exists($k, $this->f)) {
          $c[$k] = $v;
        } else if (is_array($this->f[$k]) or is_array($v)) {
          if ($this->f[$k] != $v) $c[$k] = $v;
        } else if ((string)$this->f[$k] !== (string)$v) {
          $c[$k] = $v;
        }
      }
      return $c;
    }
    public function save($values) {
      Rec::$actionSaves[] = $values;
      $this->f = $values + $this->f;
      return true;
    }
    public function insert($values) {
      Rec::$actionInserts[] = $values;
      $this->f = $values + array('Id' => 77);
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
  function validInt($v) {
    if (is_array($v) or is_object($v)) return 0;
    return preg_replace('/[^\-\d]/', '', (string)$v);
  }
  function validCardinal($v) {
    if (is_array($v) or is_object($v)) return 0;
    return preg_replace('/\D/', '', (string)$v);
  }
  function validDevicePath($v) { return is_string($v) ? $v : ''; }
  function validHtmlStr($v) { return is_string($v) ? $v : ''; }
  function dbFetchOne($sql, $col = null, $args = array()) {
    return (strpos($sql, 'MAX(Sequence)') !== false) ? 7 : null;
  }
  function dbFetchAll($sql, $col = null, $args = array()) {
    return (strpos($sql, 'FROM Zones') !== false) ? ZM\Rec::$zones : array();
  }
  function dbQuery($sql, $args = array()) {
    ZM\Rec::$queries[] = array($sql, $args);
  }
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
  function getPolyArea($p) { return 0; }
  function convertPixelPointsToPercent($p, $w, $h, $r = false) {}
  function convertZoneThresholdsToPercent($z, $a, $r = false) {}
  function getFormChanges($old, $new, $types = false) {
    $c = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $c[$k] = "`$k` = '$v'";
    }
    return $c;
  }

  // Cron helpers the newAction block calls. The real CronTab.php is replaced by
  // an empty stub in the temp includes dir, so these stay in effect.
  function cronFromParts($frequency, $hour, $minute) { return '0 8 * * *'; }
  function cronScheduleIsValid($spec) { return is_string($spec) and trim($spec) !== ''; }
  function cronRebuildScheduledActions() { return true; }

  // The action takes its checkbox defaults from this helper where it exists.
  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  $test_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/monitor.php';
  if (!file_exists($test_file)) {
    echo "FAIL - monitor.php not found at $test_file\n";
    exit(1);
  }

  // The action pulls its classes in with relative require_once calls, so run
  // with a temp cwd that has an includes/ dir of empty stubs. This also makes
  // relative requires resolve when $test_file lives outside web/.
  $dir = sys_get_temp_dir().'/zm_test_newaction_'.getmypid();
  @mkdir($dir.'/includes', 0755, true);
  @mkdir(sys_get_temp_dir().'/zm_test_newaction_storage', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model',
      'MonitorAction', 'CronTab') as $f) {
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

  // One existing monitor, unchanged by the request so the monitor save path
  // does nothing and only the newAction block is exercised.
  function monitor_db() {
    return array(5 => array(
      'Id' => 5, 'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Deleted' => 0,
      'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0,
      'ManufacturerId' => '', 'ModelId' => '',
      'Triggers' => array(), 'TrackMotion' => 0, 'ModectDuringPTZ' => 0,
      'RTSP2WebEnabled' => 0, 'Go2RTCEnabled' => 0, 'Go2RTCDisableBackChannel' => 0,
      'JanusEnabled' => 0, 'JanusAudioEnabled' => 0, 'Restream' => 0, 'Exif' => 0,
      'V4LMultiBuffer' => '', 'WallClockTimestamps' => 0, 'RecordAudio' => 0,
      'AudioDetection' => 0, 'Method' => 'raw', 'GroupIds' => array(),
      'LinkedMonitors' => array(), 'MQTT_Enabled' => 0, 'RTSPServer' => 0,
      'SectionLengthWarn' => 0, 'SOAP_wsa_compl' => 0,
    ));
  }

  // Returns array($thrown, $error_message). $newAction is the raw value posted
  // for $_REQUEST['newAction'] so a caller can post something that is not a
  // list of arrays at all.
  function run_action($newAction, $db = null) {
    global $error_message, $user, $action, $redirect, $test_file;
    if ($db === null) $db = monitor_db();
    ZM\Rec::$saved = ZM\Rec::$inserted = ZM\Rec::$queries = array();
    ZM\Rec::$actionInserts = ZM\Rec::$actionSaves = array();
    ZM\Rec::$db = $db;
    ZM\Rec::$zones = array();
    $_REQUEST = array(
      'mid' => '5',
      'newMonitor' => array(
        'Id' => '5', 'ManufacturerId' => '', 'Manufacturer' => '',
        'ModelId' => '', 'Model' => '', 'Name' => 'Garage', 'Type' => 'Ffmpeg',
        'Width' => '640', 'Height' => '480',
      ),
      'newAction' => $newAction,
    );
    $error_message = '';
    $redirect = null;
    $user = new FakeUser();
    $action = 'save';
    $thrown = null;
    ob_start();
    try {
      include $test_file;
    } catch (\Throwable $e) {
      $thrown = get_class($e).': '.$e->getMessage();
    }
    ob_end_clean();
    return array($thrown, $error_message);
  }

  function saved_count() {
    return count(ZM\Rec::$actionInserts) + count(ZM\Rec::$actionSaves);
  }

  // 1. ScheduleRaw posted as an array: fall back to frequency + time.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => 'Schedule', 'ActionType' => 'LightOn',
    'ScheduleRaw' => array('x'), 'ScheduleFrequency' => 'daily',
    'ScheduleTime' => '08:00',
  )));
  check('1 an array ScheduleRaw does not throw', $thrown, null);
  check('1 an array ScheduleRaw saves one action', saved_count(), 1);
  check('1 an array ScheduleRaw uses the frequency/time schedule',
    ZM\Rec::$actionInserts[0]['Schedule'] ?? null, '0 8 * * *');

  // 2. TriggerOn must be one of the known triggers.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => 'Bogus', 'ActionType' => 'LightOn',
  )));
  check('2 a bogus trigger does not throw', $thrown, null);
  check('2 a bogus trigger saves nothing', saved_count(), 0);
  check('2 a bogus trigger reports an error', $err !== '', true);

  // 3. ActionType must be one of the known action types.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => 'EventStart', 'ActionType' => 'Bogus',
  )));
  check('3 a bogus action type does not throw', $thrown, null);
  check('3 a bogus action type saves nothing', saved_count(), 0);
  check('3 a bogus action type reports an error', $err !== '', true);

  // 4. TriggerOn posted as an array.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => array('x'),
  )));
  check('4 an array TriggerOn does not throw', $thrown, null);
  check('4 an array TriggerOn saves nothing', saved_count(), 0);

  // 5. The row itself is a plain string, not an array.
  list($thrown, $err) = run_action(array('x'));
  check('5 a string row does not throw', $thrown, null);
  check('5 a string row saves nothing', saved_count(), 0);

  // 6. The happy path.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => 'EventStart', 'ActionType' => 'AudioPlay',
  )));
  check('6 a valid row does not throw', $thrown, null);
  check('6 a valid row saves one action', saved_count(), 1);
  check('6 the saved trigger is kept', ZM\Rec::$actionInserts[0]['TriggerOn'] ?? null, 'EventStart');
  check('6 the saved action type is kept', ZM\Rec::$actionInserts[0]['ActionType'] ?? null, 'AudioPlay');

  // 7. AudioFile posted as an array on an otherwise valid row.
  list($thrown, $err) = run_action(array(array(
    'TargetMonitorId' => '2', 'TriggerOn' => 'EventStart', 'ActionType' => 'AudioPlay',
    'AudioFile' => array('9'),
  )));
  check('7 an array AudioFile does not throw', $thrown, null);
  check('7 an array AudioFile saves one action', saved_count(), 1);
  $audio = array_key_exists('AudioFile', ZM\Rec::$actionInserts[0])
    ? ZM\Rec::$actionInserts[0]['AudioFile'] : 'missing';
  check('7 an array AudioFile is dropped', $audio, null);

  system('rm -rf '.escapeshellarg($dir).' '
    .escapeshellarg(sys_get_temp_dir().'/zm_test_newaction_storage'));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
