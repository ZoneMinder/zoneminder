<?php
// The filter editor shows a DateTime/Time term value with the ISO "T"
// separator replaced by a space. Only that separator may change: a value such
// as "Today" or "CURDATE()" must reach the input unchanged, or saving the form
// stores a value strtotime() cannot read and the term stops matching.
//
// Run as: php tests/php/test_filter_widget_datetime_value.php

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
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

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
function htmlSelect($name, $contents, $values, $behaviours = false) { return '<select name="'.$name.'"></select>'; }

require_once __DIR__.'/../../web/includes/Filter.php';

$Servers = array();

function value_shown($attr, $val) {
  $filter = new Filter();
  $filter->addTerm(array('attr'=>$attr, 'op'=>'>=', 'val'=>$val));
  $html = $filter->widget();
  return preg_match('/name="filter\[Query\]\[terms\]\[0\]\[val\]"[^>]*value="([^"]*)"/', $html, $m) ? $m[1] : null;
}

foreach (array('StartDateTime', 'EndDateTime') as $attr) {
  check("$attr ISO separator becomes a space",
    value_shown($attr, '2026-08-09T10:11:12'), '2026-08-09 10:11:12');
  check("$attr keeps Today", value_shown($attr, 'Today'), 'Today');
  check("$attr keeps CURDATE()", value_shown($attr, 'CURDATE()'), 'CURDATE()');
}
check('StartTime keeps Tomorrow', value_shown('StartTime', 'Tomorrow'), 'Tomorrow');

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
