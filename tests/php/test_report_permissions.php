<?php
// The report view had no permission check and printed every event of the report's filter,
// including events of monitors the user may not view (an OR term escapes the monitor term
// the view adds). Creating a report needed no permission.
//
// Run as: php tests/php/test_report_permissions.php [web root]

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  class Report {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function FilterId() { return 5; }
    function Name() { return 'r'; }
    function StartDateTime() { return ''; }
    function EndDateTime() { return ''; }
    function Interval() { return ''; }
    function canEdit() { return $GLOBALS['report_can_edit']; }
    function changes($v) { return array('Name' => 'n'); }
    function save($c) { $GLOBALS['saved'] = true; if (!$this->id) $this->id = 12; return true; }
  }
  class Event {
    public $row;
    function __construct($row) { $this->row = $row; }
    function canView() { return $this->row['MonitorId'] == 1; }
    function to_json() { return json_encode($this->row); }
  }
  class Filter {
    function __construct($id = null) {}
    function Id() { return 5; }
    function addTerm($t) { return $this; }
    static function find() { return array(5 => new Filter(5)); }
    function Events() {
      return array(new Event(array('Id' => 1, 'MonitorId' => 1, 'Name' => 'visible')),
                   new Event(array('Id' => 2, 'MonitorId' => 2, 'Name' => 'HIDDEN-EVENT')));
    }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  $stub = sys_get_temp_dir().'/zm_report_perm_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Event', 'Filter', 'Report') as $f) file_put_contents($stub."/includes/$f.php", "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  $GLOBALS['can_view'] = true;
  $GLOBALS['can_edit'] = true;
  $GLOBALS['report_can_edit'] = true;
  function canView($a) { return $GLOBALS['can_view']; }
  function canEdit($a) { return $GLOBALS['can_edit']; }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function i18n() { return 'en'; }
  function xhtmlFooter() {}
  function htmlSelect($n, $o, $s) { return ''; }
  class StubUser {
    function unviewableMonitorIds() { return array(2); }
    function viewableMonitorIds() { return array(1); }
    function Id() { return 3; }
  }
  $user = new StubUser();
  $cspNonce = 'n';

  // The view
  $_REQUEST = array('id' => 1);
  $GLOBALS['can_view'] = false;
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('view without Events permission shows the error page', $view, 'error');
  check('view without Events permission prints no events', strpos($out, 'events[') === false, true);

  $GLOBALS['can_view'] = true;
  $GLOBALS['can_edit'] = true;
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('view for a permitted user keeps the view', $view, 'report');
  check('view omits events of a monitor the user cannot view', strpos($out, 'HIDDEN-EVENT'), false);
  check('view keeps visible events', strpos($out, 'visible') !== false, true);

  // The buttons reflect the same permission checks as the action
  $GLOBALS['can_edit'] = false;
  $GLOBALS['report_can_edit'] = false;
  $_REQUEST = array('id' => 1);
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('Save is disabled when the report cannot be edited',
    (bool)preg_match('/id="saveBtn"[^>]*disabled="disabled"/', $out), true);
  check('the disabled Save says why',
    (bool)preg_match('/id="saveBtn"[^>]*title="EditReportDisabled"/', $out), true);

  $_REQUEST = array();
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('Save is disabled for a new report without Events edit',
    (bool)preg_match('/id="saveBtn"[^>]*disabled="disabled"/', $out), true);
  check('the disabled new-report Save says why',
    (bool)preg_match('/id="saveBtn"[^>]*title="AddReportDisabled"/', $out), true);

  $GLOBALS['can_edit'] = true;
  $GLOBALS['report_can_edit'] = true;
  $_REQUEST = array('id' => 1);
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('Save stays enabled when the report can be edited',
    (bool)preg_match('/id="saveBtn"[^>]*disabled="disabled"/', $out), false);

  $GLOBALS['can_edit'] = false;
  $_REQUEST = array();
  $view = 'reports';
  ob_start();
  include $root.'/skins/classic/views/reports.php';
  $out = ob_get_clean();
  check('Add New is disabled without Events edit',
    (bool)preg_match('/id="newBtn"[^>]*disabled="disabled"/', $out), true);
  check('the disabled Add New says why',
    (bool)preg_match('/id="newBtn"[^>]*title="AddReportDisabled"/', $out), true);

  // The action
  $GLOBALS['can_view'] = true;
  $_REQUEST = array();
  $action = 'save';
  $redirect = null;
  $error_message = '';
  $GLOBALS['can_edit'] = false;
  $GLOBALS['report_can_edit'] = false;
  $GLOBALS['saved'] = false;
  include $root.'/includes/actions/report.php';
  check('creating a report without Events edit is refused', $GLOBALS['saved'], false);
  check('creating a report without Events edit explains why', $error_message !== '', true);
  check('creating a report without Events edit does not redirect', $redirect, null);

  $_REQUEST = array('id' => 1);
  $redirect = null;
  $error_message = '';
  $GLOBALS['saved'] = false;
  include $root.'/includes/actions/report.php';
  check('editing a report the user cannot edit is refused', $GLOBALS['saved'], false);
  check('editing a report the user cannot edit explains why', $error_message !== '', true);
  check('editing a report the user cannot edit does not redirect', $redirect, null);

  $GLOBALS['can_edit'] = true;
  $GLOBALS['report_can_edit'] = true;
  $_REQUEST = array();
  $redirect = null;
  $error_message = '';
  $GLOBALS['saved'] = false;
  include $root.'/includes/actions/report.php';
  check('creating a report with Events edit works', $GLOBALS['saved'], true);
  check('an allowed save redirects with no message', array($error_message, $redirect !== null), array('', true));

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
