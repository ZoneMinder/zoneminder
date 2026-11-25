<?php
// `new ZM\Report($id)` always yields an object, so ?view=report&id=N for an id that matches no
// row (a report someone else deleted, a stale bookmark) rendered the blank new-report form, and
// saving it was refused by the action and sent the user back to the list with the name lost.
// The view shows the error page for such an id, like the user and role views do.
//
// Run as: php tests/php/test_report_view_missing_id.php [web root]

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  class Report {
    public $id;
    function __construct($id = null) { $this->id = ($id == 7) ? 7 : null; }
    function Id() { return $this->id; }
    function FilterId() { return null; }
    function Name() { return 'r'; }
    function StartDateTime() { return ''; }
    function EndDateTime() { return ''; }
    function Interval() { return ''; }
  }
  class Filter {
    function __construct($id = null) {}
    static function find() { return array(); }
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
  $stub = sys_get_temp_dir().'/zm_report_missing_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Event', 'Filter', 'Report') as $f) file_put_contents($stub."/includes/$f.php", "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  function canView($a) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function xhtmlFooter() {}
  function htmlSelect($n, $o, $s) { return ''; }
  $cspNonce = 'n';

  function render($req) {
    $_REQUEST = $req;
    $view = 'report';
    ob_start();
    include $GLOBALS['root'].'/skins/classic/views/report.php';
    $out = ob_get_clean();
    return array($view, $out);
  }

  list($v, $out) = render(array('id' => '999'));
  check('an id that matches no report shows the error page', $v, 'error');
  check('an id that matches no report prints no form', strpos($out, 'reportForm'), false);
  list($v, $out) = render(array('id' => '7'));
  check('an existing report keeps the view', $v, 'report');
  check('an existing report prints its form', strpos($out, 'reportForm') !== false, true);
  list($v, $out) = render(array());
  check('no id is a new report and keeps the view', $v, 'report');
  check('a new report prints its form', strpos($out, 'reportForm') !== false, true);

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
