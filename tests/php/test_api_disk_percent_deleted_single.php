<?php
// HostController::getDiskPercent($mid) must treat a soft-deleted monitor
// (Monitors.Deleted = 1) as invalid, as the all-monitors report already leaves
// it out, instead of reporting its disk usage.
//
// Run as: php tests/php/test_api_disk_percent_deleted_single.php [repo-root]

namespace ZM {
  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function canView() { return true; }
    public function Name() { return $this->row['Name']; }
    public function WebColour() { return $this->row['WebColour']; }
  }
}

namespace {
  class NotFoundException extends \Exception {}
  class UnauthorizedException extends \Exception {}
  function __($s) { return $s; }
  define('ZM_DIR_EVENTS', sys_get_temp_dir());

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $failures = 0;
  function check($name, $ok) {
    global $failures;
    if (!$ok) $failures++;
    echo ($ok ? 'ok' : 'FAIL')." - $name\n";
  }

  $GLOBALS['monitors'] = array(
    1 => array('Id' => 1, 'Name' => 'Old', 'WebColour' => '#111111', 'StorageId' => 0, 'Deleted' => 1),
    3 => array('Id' => 3, 'Name' => 'Front', 'WebColour' => '#333333', 'StorageId' => 0, 'Deleted' => 0),
  );

  class StubMonitorModel {
    public function exists($id) { return isset($GLOBALS['monitors'][$id]); }
    public function find($type, $options = array()) {
      $c = $options['conditions'];
      $m = isset($GLOBALS['monitors'][$c['Id']]) ? $GLOBALS['monitors'][$c['Id']] : null;
      if ($m && isset($c['Monitor.Deleted']) && $m['Deleted'] != $c['Monitor.Deleted']) $m = null;
      return $m ? array('Monitor' => $m) : array();
    }
  }

  $source = file_get_contents($root.'/web/api/app/Controller/HostController.php');
  if (!preg_match('/^  function getDiskPercent\(.*?^  }\n/ms', $source, $m)) {
    echo "FAIL - could not find getDiskPercent\n";
    exit(1);
  }
  $method = preg_replace('/^\s*require_once .*Monitor\.php.;\n/m', '', $m[0]);
  eval('class DiskPercentHarness {
    public $Monitor;
    public $viewVars;
    public function loadModel($m) { $this->Monitor = new StubMonitorModel(); }
    public function set($v) { $this->viewVars = $v; }
    private function _getMonitorDiskUsage($zm_monitor, $monitorId, $zm_dir_events) { return "used-by-".$monitorId; }
    '.$method.'
  }');

  $h = new DiskPercentHarness();
  $h->getDiskPercent(3);
  check('a current monitor is reported', isset($h->viewVars['usage']['Front']));

  $h = new DiskPercentHarness();
  $thrown = null;
  try {
    $h->getDiskPercent(1);
  } catch (\Throwable $e) {
    $thrown = get_class($e);
  }
  check('a deleted monitor is refused as not found', $thrown === 'NotFoundException');
  check('a deleted monitor is not reported', $h->viewVars === null);

  exit($failures ? 1 : 0);
}
