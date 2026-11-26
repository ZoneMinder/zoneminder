<?php
// The inline filter bar on the events page sends several selected tags as
// "1,2" in the URL. After a reload the Tags select must show those tags as
// selected, not come up empty while the table is still filtered by them.
//
// Run as: php tests/php/test_filter_simple_widget_tags.php

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

$stubdir = sys_get_temp_dir().'/zm_filter_tags_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function dbFetchAll($sql) {
  if (strpos($sql, 'FROM Tags') !== false) {
    return array(array('Id'=>'1', 'Name'=>'a'), array('Id'=>'2', 'Name'=>'b'), array('Id'=>'3', 'Name'=>'c'));
  }
  return array();
}
function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function translate($s) { return $s; }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
function validHtmlStr($s) { return htmlspecialchars($s); }
function html_input($n, $t, $v) { return ""; }
$tagsSelected = null;
function htmlSelect($name, $contents, $values, $behaviours = false) {
  global $tagsSelected;
  if (is_array($behaviours) and isset($behaviours['id']) and $behaviours['id'] == 'filterTags') $tagsSelected = $values;
  return '<select name="'.$name.'"></select>';
}

require_once __DIR__.'/../../web/includes/Filter.php';

$Servers = array();

function selected_tags($val) {
  global $tagsSelected;
  $tagsSelected = null;
  $filter = new Filter();
  $filter->addTerm(array('attr'=>'Tags', 'op'=>'=', 'val'=>$val));
  $filter->simple_widget();
  $v = $tagsSelected;
  if ($v === null) return null;
  $v = array_map('strval', (array)$v);
  sort($v);
  return $v;
}

check('URL form 1,2', selected_tags('1,2'), array('1', '2'));
check('cookie form JSON list', selected_tags('["1","3"]'), array('1', '3'));
check('single tag', selected_tags('3'), array('3'));
check('No Tag', selected_tags('0'), array('0'));
check('empty value selects nothing', selected_tags(''), array());

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
