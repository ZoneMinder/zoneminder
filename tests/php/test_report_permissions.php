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
    function canEdit() { return true; }
    function changes($v) { return array('Name' => 'n'); }
    function save($c) { $GLOBALS['saved'] = true; return true; }
  }
  class Event {
    public $row;
    function __construct($row) { $this->row = $row; }
    function canView() { return $this->row['MonitorId'] == 1; }
    function to_json() { return json_encode($this->row); }
  }
  class Filter {
    function __construct($id = null) {}
    function addTerm($t) { return $this; }
    static function find() { return array(); }
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

  $GLOBALS['perm'] = true;
  function canView($a) { return $GLOBALS['perm']; }
  function canEdit($a) { return $GLOBALS['perm']; }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
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
  $GLOBALS['perm'] = false;
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('view without Events permission shows the error page', $view, 'error');
  check('view without Events permission prints no events', strpos($out, 'events[') === false, true);

  $GLOBALS['perm'] = true;
  $view = 'report';
  ob_start();
  include $root.'/skins/classic/views/report.php';
  $out = ob_get_clean();
  check('view for a permitted user keeps the view', $view, 'report');
  check('view omits events of a monitor the user cannot view', strpos($out, 'HIDDEN-EVENT'), false);
  check('view keeps visible events', strpos($out, 'visible') !== false, true);

  // The action
  $_REQUEST = array();
  $action = 'save';
  $redirect = null;
  $error_message = '';
  $GLOBALS['perm'] = false;
  $GLOBALS['saved'] = false;
  include $root.'/includes/actions/report.php';
  check('creating a report without Events edit is refused', $GLOBALS['saved'], false);
  $GLOBALS['perm'] = true;
  include $root.'/includes/actions/report.php';
  check('creating a report with Events edit works', $GLOBALS['saved'], true);

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
