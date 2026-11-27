<?php
// The export view picks the events from eid or eids in the request and binds them to its
// query. eids[][]=5 made urlencode() throw a TypeError when the selection link for the column
// headers was built, a fatal error. eid[]=5 was bound as an array, an "Array to string
// conversion" warning, and matched "Array", so the view listed no event. A value that is no id
// is left out; with nothing left the view lists the events as it does without either parameter.
//
// The SQL block is cut out of export.php and run with stubs.
// Run as: php tests/php/test_export_eid_array.php [root]   (root defaults to the repo)

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
    public static function parse($f) { return new Filter(); }
    public function querystring() { return ""; }
    public function sql() { return ""; }
  } }');

class StubUser {
  public function viewableMonitorIds() { return array(); }
  public function unviewableMonitorIds() { return array(); }
}

$source = file_get_contents($root.'/web/skins/classic/views/export.php');
$start = strpos($source, '$eventsSql = ');
$end = strpos($source, '$results = dbQuery(');
if ($start === false or $end === false) {
  echo "FAIL - could not find the events SQL block in export.php\n";
  exit(1);
}
$block = substr($source, $start, $end - $start);

set_error_handler(function($no, $str) { throw new ErrorException($str); });

// Runs the block for a request; returns the SQL, bound values and selection query, or the error.
function run($block, $request) {
  global $view, $sortQuery, $sortColumn, $sortOrder, $limitQuery, $user;
  $view = 'export';
  $user = new StubUser();
  $_REQUEST = $request;
  try {
    eval($block);
    return array('error' => null, 'sql' => $eventsSql, 'values' => $eventsValues, 'selection' => $selectionQuery);
  } catch (Throwable $e) {
    return array('error' => get_class($e).': '.$e->getMessage(), 'sql' => '', 'values' => null, 'selection' => null);
  }
}

$r = run($block, array('view' => 'export', 'eid' => '5'));
check('eid: one event', array($r['error'], strpos($r['sql'], 'E.Id=?') !== false, $r['values']), array(null, true, array('5')));

$r = run($block, array('view' => 'export', 'eids' => array('5', '9')));
check('eids: selected events', array($r['error'], strpos($r['sql'], 'E.Id IN (?,?)') !== false, $r['values']), array(null, true, array('5', '9')));
check('eids: selection kept for the sort links', $r['selection'], 'eids%5B%5D=5&amp;eids%5B%5D=9');

$r = run($block, array('view' => 'export', 'eid' => array('5')));
check('eid array: nothing bound, events listed as without eid', array($r['error'], strpos($r['sql'], 'E.Id') === false, $r['values']), array(null, true, array()));

$r = run($block, array('view' => 'export', 'eids' => array('5', array('9'))));
check('eids with a nested element: the id is kept', array($r['error'], strpos($r['sql'], 'E.Id IN (?)') !== false, $r['values'], $r['selection']), array(null, true, array('5'), 'eids%5B%5D=5'));

$r = run($block, array('view' => 'export', 'eids' => array(array('9'))));
check('eids of only nested elements: no IN ()', array($r['error'], strpos($r['sql'], 'IN (') === false, $r['values']), array(null, true, array()));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
