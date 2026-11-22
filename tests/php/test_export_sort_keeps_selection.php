<?php
// The export view's column headers (sortHeader()) dropped eids[] and the filter, so sorting an
// export of two selected events reloaded the page listing every event. The eids and eid
// branches also had no ORDER BY, and the restricted-monitor condition was joined to the
// filter SQL (or to nothing) without AND, with an empty list giving M.Id in ().
//
// The SQL block and a header link are cut out of export.php and run with stubs.
// Run as: php tests/php/test_export_sort_keeps_selection.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$functions = file_get_contents($root.'/web/includes/functions.php');
foreach (array('currentSortField', 'sortHeader', 'sortTag', 'parseSort', 'validInt', 'validCardinal', 'validHtmlStr') as $name) {
  if (!preg_match('/^function '.$name.'\(.*?^}\n/ms', $functions, $m)) {
    echo "FAIL - could not find $name in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'desc');
eval('namespace ZM { function Warning($m) {}
  class Filter {
    public static $sql = "";
    public static function parse($f) { return new Filter(); }
    public function querystring() { return "&amp;filter%5BQuery%5D%5Bsort_asc%5D=0&amp;filter%5BQuery%5D%5Bsort_field%5D=Id&amp;filter%5Bterms%5D%5B0%5D%5Battr%5D=Name"; }
    public function sql() { return self::$sql; }
  } }');

class StubUser {
  public $viewable;
  public $unviewable;
  public function __construct($v, $u) { $this->viewable = $v; $this->unviewable = $u; }
  public function viewableMonitorIds() { return $this->viewable; }
  public function unviewableMonitorIds() { return $this->unviewable; }
}

$source = file_get_contents($root.'/web/skins/classic/views/export.php');
$start = strpos($source, '$eventsSql = ');
$end = strpos($source, '$results = dbQuery(');
if ($start === false or $end === false) {
  echo "FAIL - could not find the events SQL block in export.php\n";
  exit(1);
}
$block = substr($source, $start, $end - $start);
if (!preg_match('/<a href="<\?php echo (sortHeader\(\'Name\'[^?]*?) \?>"/', $source, $m)) {
  echo "FAIL - could not find the Name column header\n";
  exit(1);
}
$headerCode = $m[1];

// Runs the block for a request and user; returns [sql, values, Name header link].
function run($block, $headerCode, $request, $user, $filterSql = '') {
  global $view, $sortQuery, $sortColumn, $sortOrder, $limitQuery;
  $view = 'export';
  $_REQUEST = $request;
  ZM\Filter::$sql = $filterSql;
  eval($block);
  return array($eventsSql, $eventsValues, eval('return '.$headerCode.';'));
}

$all = new StubUser(array(), array());
$restricted = new StubUser(array(1, 2), array(3));
$none = new StubUser(array(), array(3));

// Selection of two events, then the Name header
list($sql, $values, $link) = run($block, $headerCode, array('view'=>'export', 'eids'=>array('5', '9')), $all);
check('eids: selected events only', strpos($sql, 'E.Id IN (?,?)') !== false, true);
check('eids: bound ids', $values, array('5', '9'));
check('eids: honours the sort', (bool)preg_match('/ ORDER BY E\.StartDateTime desc$/', $sql), true);
check('eids: header link keeps the selection', strpos($link, 'eids%5B%5D=5&amp;eids%5B%5D=9') !== false, true);
check('eids: header link sorts by Name', strpos($link, 'sort_field=Name') !== false, true);

// Clicking the header: the request now has the sort
list($sql, $values, $link) = run($block, $headerCode, array('view'=>'export', 'eids'=>array('5', '9'), 'sort_field'=>'Name', 'sort_asc'=>'1'), $all);
check('eids sorted by Name', (bool)preg_match('/ ORDER BY E\.Name asc$/', $sql), true);
check('eids sorted: second click toggles', strpos($link, 'sort_asc=&amp;') !== false, true);

// Single event keeps working
list($sql, $values, $link) = run($block, $headerCode, array('view'=>'export', 'eid'=>'7'), $all);
check('eid: one event', array(strpos($sql, 'E.Id=?') !== false, $values), array(true, array('7')));
check('eid: header link keeps the event', strpos($link, 'eid=7') !== false, true);

// Filter selection survives, without its own sort overriding the header's
list($sql, $values, $link) = run($block, $headerCode, array('view'=>'export', 'filter'=>array('Query'=>array('sort_field'=>'Id', 'sort_asc'=>'0'))), $all, ' Name = 1');
check('filter: header link keeps the terms', strpos($link, 'filter%5Bterms%5D%5B0%5D%5Battr%5D=Name') !== false, true);
check('filter: header link drops the filter sort', strpos($link, 'filter%5BQuery%5D%5Bsort_field%5D') === false, true);
check('filter: where is wrapped', strpos($sql, 'WHERE ( Name = 1)') !== false, true);

// WHERE assembly
list($sql) = run($block, $headerCode, array('view'=>'export'), $all);
check('no restriction: everything', strpos($sql, 'WHERE 1 ORDER BY') !== false, true);
list($sql) = run($block, $headerCode, array('view'=>'export', 'filter'=>array()), $restricted, ' Name = 1');
check('restricted + filter joined with AND', strpos($sql, 'WHERE M.Id IN (1,2) AND ( Name = 1) ORDER BY') !== false, true);
list($sql) = run($block, $headerCode, array('view'=>'export', 'eids'=>array('5')), $restricted);
check('restricted + eids joined with AND', strpos($sql, 'WHERE M.Id IN (1,2) AND E.Id IN (?)') !== false, true);
list($sql) = run($block, $headerCode, array('view'=>'export'), $none);
check('no viewable monitor matches nothing', strpos($sql, 'M.Id IN (0)') !== false and !preg_match('/IN\s*\(\s*\)/', $sql), true);
list($sql) = run($block, $headerCode, array('view'=>'export', 'filter'=>array()), $all, '');
check('filter without terms is not an empty WHERE', strpos($sql, 'WHERE 1 ORDER BY') !== false, true);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
