<?php
// HostController::getDiskPercent() without a monitor id reports usage per monitor,
// keyed by name. Monitors are only soft-deleted (Monitors.Deleted), so the
// monitor list has to leave the deleted ones out, as MonitorsController::index()
// does, instead of reporting monitors that no longer exist.
//
// HostController can't be loaded without CakePHP, so the method is taken from the
// source and run in a stand-in with the Monitor model stubbed.
//
// Run as: php tests/php/test_api_disk_percent_deleted_monitors.php

namespace ZM {
  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function canView() { return true; }
  }
}

namespace {
  define('ZM_DIR_EVENTS', sys_get_temp_dir());

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

  // Monitors 1 and 2 were deleted.
  $GLOBALS['monitors'] = array(
    array('Id' => 1, 'Name' => 'Old', 'WebColour' => '#111111', 'StorageId' => 0, 'Deleted' => 1),
    array('Id' => 2, 'Name' => 'Yard', 'WebColour' => '#222222', 'StorageId' => 0, 'Deleted' => 1),
    array('Id' => 3, 'Name' => 'Front', 'WebColour' => '#333333', 'StorageId' => 0, 'Deleted' => 0),
  );

  class StubMonitorModel {
    public function exists($id) { return true; }
    public function find($type, $options = array()) {
      $conditions = isset($options['conditions']) ? $options['conditions'] : array();
      $rows = array();
      foreach ($GLOBALS['monitors'] as $m) {
        if (isset($conditions['Monitor.Deleted']) and $m['Deleted'] != $conditions['Monitor.Deleted']) continue;
        $rows[] = array('Monitor' => $m);
      }
      return $rows;
    }
  }

  $source = file_get_contents(__DIR__.'/../../web/api/app/Controller/HostController.php');
  if (!preg_match('/^  function getDiskPercent\(.*?^  }\n/ms', $source, $m)) {
    echo "FAIL - could not find getDiskPercent in HostController.php\n";
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
  $h->getDiskPercent();
  $usage = $h->viewVars['usage'];
  unset($usage['Total']);

  check('only current monitors are listed', array_keys($usage), array('Front'));
  check('the current monitor is reported', $usage['Front'], array('space' => 'used-by-3', 'color' => '#333333'));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
