<?php
// The monitor filter bar (buildMonitorsFilters() in views/_monitor_filters.php)
// keeps a selected MonitorId only if it survives the Name/Source filters. When
// the selected monitor is hidden by the Name filter the selection should be
// dropped and the monitors that do match should still be shown; keeping the
// stale selection instead hides every monitor. The view is loaded with stub
// users, database rows and ZM classes, as the other tests/php filter tests do.
//
// Run as: php tests/php/test_monitor_filter_selected_hidden_by_name.php [repo root]
// (from the repo root the argument can be omitted)

namespace ZM {
  class Storage {
    public static function find() { return array(); }
  }

  class Group {
    public static function get_dropdown_options() { return array(); }
    public static function get_group_sql($group_id) { return ''; }
    public static function find() { return array(); }
  }

  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function Id() { return $this->row['Id']; }
    public function Name() { return $this->row['Name']; }
    public function Source() { return isset($this->row['Source']) ? $this->row['Source'] : ''; }
    public function Path() { return isset($this->row['Path']) ? $this->row['Path'] : ''; }
  }
}

namespace {
  $failures = 0;
  function check($name, $ok, $detail = '') {
    global $failures;
    if ($ok) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name".($detail !== '' ? " ($detail)" : '')."\n";
    }
  }

  class StubUser {
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(1, 2, 3); }
  }

  function translate($s) { return $s; }

  function htmlSelect($name, $options, $selected = '', $attributes = array()) {
    return 'SELECT:'.$name.':'.json_encode($options).':'.json_encode($selected);
  }

  function canView($x) { return false; }
  function visibleMonitor($id) { return true; }
  function validHtmlStr($s) { return htmlspecialchars($s, ENT_QUOTES); }
  function zm_setcookie($name, $value = '') {}

  function dbFetchAll($sql, $a = null, $v = null) {
    return array(
      array('Id' => 1, 'Name' => 'Front',  'Source' => 'rtsp://front',  'Path' => '/front',  'Sequence' => 1),
      array('Id' => 2, 'Name' => 'Back',   'Source' => 'rtsp://back',   'Path' => '/back',   'Sequence' => 2),
      array('Id' => 3, 'Name' => 'Garage', 'Source' => 'rtsp://garage', 'Path' => '/garage', 'Sequence' => 3),
    );
  }

  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);

  // buildMonitorsFilters() require_once's includes/Monitor.php, so put an empty
  // shadow of it first on the include path. Otherwise the real ZM\Monitor would
  // be loaded and clash with the stub defined above.
  $tmp = sys_get_temp_dir().'/zm_monitor_filter_test_'.getmypid().'_'.mt_rand();
  mkdir($tmp);
  mkdir($tmp.'/includes');
  file_put_contents($tmp.'/includes/Monitor.php', "<?php\n");
  $old_include_path = get_include_path();
  set_include_path($tmp.PATH_SEPARATOR.$old_include_path);

  // The view only defines functions, so it is required once; each case then
  // just resets the request/cookie state and calls buildMonitorsFilters().
  require_once $root.'/web/skins/classic/views/_monitor_filters.php';

  $user = new StubUser();
  $Servers = array();

  function runCase($request) {
    $_REQUEST = $request;
    $_COOKIE = array();
    return buildMonitorsFilters();
  }

  function monitorIds($rows) {
    $ids = array();
    foreach ($rows as $row) $ids[] = $row['Id'];
    return $ids;
  }

  // Case 1: the selected monitor 2 is hidden by the Name filter, so the
  // selection must be dropped and the matching monitor 1 shown.
  $result = runCase(array('MonitorId' => array('2'), 'MonitorName' => 'Front'));
  check('hidden selected monitor dropped', $result['selected_monitor_ids'] === array(),
    json_encode($result['selected_monitor_ids']));
  check('displayMonitors is monitor 1', monitorIds($result['displayMonitors']) === array(1),
    json_encode(monitorIds($result['displayMonitors'])));

  // Case 2: the selected monitor 2 matches the Name filter, so it is kept and
  // shown on its own.
  $result = runCase(array('MonitorId' => array('2'), 'MonitorName' => 'Back'));
  check('matching selected monitor kept', $result['selected_monitor_ids'] === array('2'),
    json_encode($result['selected_monitor_ids']));
  check('displayMonitors is monitor 2', monitorIds($result['displayMonitors']) === array(2),
    json_encode(monitorIds($result['displayMonitors'])));

  set_include_path($old_include_path);
  @unlink($tmp.'/includes/Monitor.php');
  @rmdir($tmp.'/includes');
  @rmdir($tmp);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
