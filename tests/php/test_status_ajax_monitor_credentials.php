<?php
// The monitor entity of web/ajax/status.php returns Host, Path and ControlAddress, which hold
// the camera's user:password@. Those credentials are for users who can edit the monitor, so a
// caller who cannot edit it must get the fields with the userinfo removed, while a caller who
// can edit it still gets them unchanged.
//
// The functions are taken from status.php and called with stubbed SQL, permission and request
// so no database or web server is needed.
// Run as: php tests/php/test_status_ajax_monitor_credentials.php [root]   (root defaults to the repo)

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  require_once $root.'/web/includes/log_redact.php';
  $source = file_get_contents($root.'/web/ajax/status.php');
  if (!preg_match('/^function collectData\(\).*?^\}/ms', $source, $m)) {
    echo "FAIL - web/ajax/status.php has no collectData()\n";
    exit(1);
  }
  $collectSource = $m[0];
  // statusHideMonitorCredentials() is the fix under test; the original tree does not have it yet.
  $hideSource = null;
  if (preg_match('/^function statusHideMonitorCredentials\(.*?^\}/ms', $source, $m)) $hideSource = $m[0];

  $GLOBALS['statusData'] = array('monitor' => array(
    'object' => 'Monitor',
    'table' => 'Monitors',
    'limit' => 1,
    'selector' => 'Monitors.Id',
    'elements' => array(
      'Id' => array('sql' => 'Monitors.Id'),
      'Name' => true,
      'Host' => true,
      'Path' => true,
      'ControlAddress' => true,
    ),
  ));
  function validJsStr($s) { return $s; }
  function validInt($v) { return (int)$v; }
  function ajaxError($m) { $GLOBALS['ajaxErrors'][] = $m; }
  function editableMonitor($id) { return in_array($id, $GLOBALS['editable']); }
  // collectData() binds the selector as :selector0, so reset($values) is the monitor id.
  function dbFetchOne($sql, $col = NULL, $values = array()) {
    return $GLOBALS['rows'][(int)reset($values)] ?? false;
  }
}

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  class Monitor {
    private $row;
    function __construct($row) { $this->row = $row; }
    function Id() { return is_array($this->row) ? $this->row['Id'] : $this->row; }
    function canView() { return true; }
  }
}

namespace {
  eval($collectSource);
  if ($hideSource !== null) eval($hideSource);

  $row = array(
    'Name' => 'Porch',
    'Host' => 'admin:hunter2@10.0.0.9',
    'Path' => 'rtsp://admin:hunter2@10.0.0.9/live',
    'ControlAddress' => 'root:camsecret@10.0.0.9:80',
  );
  $GLOBALS['rows'] = array(
    1 => array('Id' => 1) + $row,
    2 => array('Id' => 2) + $row,
  );
  // Monitor 1 is only viewable; monitor 2 may be edited.
  $GLOBALS['editable'] = array(2);

  $_REQUEST = array('entity' => 'monitor', 'id' => 1);
  $result = collectData();
  check('id 1 keeps the Name', $result['Name'], 'Porch');
  check('id 1 Host without userinfo', $result['Host'], '10.0.0.9');
  check('id 1 Path without userinfo', $result['Path'], 'rtsp://10.0.0.9/live');
  check('id 1 ControlAddress without userinfo', $result['ControlAddress'], '10.0.0.9:80');
  $json = json_encode($result);
  check('id 1 json has no Host password', strpos($json, 'hunter2'), false);
  check('id 1 json has no ControlAddress password', strpos($json, 'camsecret'), false);

  $_REQUEST = array('entity' => 'monitor', 'id' => 2);
  $result = collectData();
  check('id 2 Host unchanged', $result['Host'], 'admin:hunter2@10.0.0.9');
  check('id 2 Path unchanged', $result['Path'], 'rtsp://admin:hunter2@10.0.0.9/live');
  check('id 2 ControlAddress unchanged', $result['ControlAddress'], 'root:camsecret@10.0.0.9:80');

  exit($failures ? 1 : 0);
}
