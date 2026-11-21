<?php
// The filter editor renders list terms as multiple selects. They must be named
// [val][] so PHP receives every chosen option, and a stored comma list must be
// shown selected. A list posted as an array is stored as the comma string the
// rest of ZoneMinder reads.
//
// Run as: php tests/php/test_filter_multi_values.php [repo root]

namespace ZM;


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

$stubdir = sys_get_temp_dir().'/zm_filter_widget_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
$root = isset($argv[1]) ? $argv[1] : realpath(__DIR__.'/../..');
set_include_path($stubdir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function dbFetchAll() { return array(); }
function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function translate($s) { return $s; }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
function validHtmlStr($s) { return htmlspecialchars($s); }
function htmlSelect($name, $contents, $values, $behaviours = false) {
  return '<select name="'.$name.'"'.(is_array($behaviours) && isset($behaviours['multiple']) ? ' multiple' : '').' data-selected="'.implode(',', (array)$values).'"></select>';
}
function visibleMonitor($id) { return true; }
class Storage { public static $table = 'Storage'; public static $primary_key = 'Id'; }
class Zone { public static $table = 'Zones'; public static $primary_key = 'Id'; }

require_once $root.'/web/includes/Filter.php';

$Servers = array();

function select_for($attr, $val) {
  $filter = new Filter();
  $filter->addTerm(array('attr'=>$attr, 'op'=>'IN', 'val'=>$val));
  $html = $filter->widget();
  return preg_match('/<select name="filter\[Query\]\[terms\]\[0\]\[val\]([^"]*)"( multiple)? data-selected="([^"]*)"/', $html, $m)
    ? array('suffix'=>$m[1], 'multiple'=>$m[2] !== '', 'selected'=>$m[3]) : null;
}

foreach (array('Monitor'=>'1,3', 'MonitorName'=>'a,b', 'ServerId'=>'1,3', 'StorageId'=>'1,3',
    'AlarmedZoneId'=>'1,3', 'Tags'=>'1,3') as $attr=>$val) {
  $s = select_for($attr, $val);
  check("$attr is a multiple select posting an array", $s ? array($s['suffix'], $s['multiple']) : null, array('[]', true));
  check("$attr shows the stored list selected", $s ? $s['selected'] : null, $val);
}

$f = new Filter();
$f->Query(array('terms'=>array(array('attr'=>'Monitor', 'op'=>'IN', 'val'=>array('1', '3')))));
$terms = $f->terms();
check('posted array value is stored as a comma list', $terms[0]['val'], '1,3');

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
