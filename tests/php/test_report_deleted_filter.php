<?php
// Reports.FilterId has no foreign key, so deleting a filter leaves its reports pointing at an id
// that no longer exists. The report view built an empty Filter for that id, and a filter with no
// terms has no WHERE clause, so the chart plotted every event the user may view.
//
// Run as: php tests/php/test_report_deleted_filter.php [web root]

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  class Report {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function FilterId() { return $GLOBALS['report_filter_id']; }
    function Name() { return 'r'; }
    function StartDateTime() { return ''; }
    function EndDateTime() { return ''; }
    function Interval() { return ''; }
  }
  class Event {
    public $row;
    function __construct($row) { $this->row = $row; }
    function canView() { return true; }
    function to_json() { return json_encode($this->row); }
  }
  class Filter {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id == 5 ? 5 : null; }
    function addTerm($t) { return $this; }
    static function find() { return array(5 => new Filter(5)); }
    function Events() {
      // An empty filter has no WHERE clause and matches every event.
      return array(new Event(array('Id' => 1, 'Name' => 'EVERY-EVENT-IN-THE-SYSTEM')));
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
  $stub = sys_get_temp_dir().'/zm_report_filter_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Event', 'Filter', 'Report') as $f) file_put_contents($stub."/includes/$f.php", "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  function canView($a) { return true; }
  function validInt($v) { return (int)$v; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function xhtmlFooter() {}
  function htmlSelect($n, $o, $s) { return ''; }
  class StubUser {
    function unviewableMonitorIds() { return array(); }
    function viewableMonitorIds() { return array(1); }
    function Id() { return 3; }
  }
  $user = new StubUser();
  $cspNonce = 'n';

  function render($filter_id) {
    global $root, $user, $cspNonce;
    $GLOBALS['report_filter_id'] = $filter_id;
    $_REQUEST = array('id' => 1);
    $view = 'report';
    ob_start();
    include $root.'/skins/classic/views/report.php';
    return ob_get_clean();
  }

  check('a report with an existing filter plots its events',
    strpos(render(5), 'EVERY-EVENT-IN-THE-SYSTEM') !== false, true);
  check('a report whose filter was deleted plots no events',
    strpos(render(99), 'EVERY-EVENT-IN-THE-SYSTEM'), false);
  check('a report with no filter plots no events',
    strpos(render(null), 'EVERY-EVENT-IN-THE-SYSTEM'), false);

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
