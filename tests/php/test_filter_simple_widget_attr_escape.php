<?php
// The events and montage review filter bars (Filter::simple_widget) take their
// terms from the request, so every term key printed into an attribute must be
// escaped: id, placeholder and data-cookie as well as the value.
//
// Run as: php tests/php/test_filter_simple_widget_attr_escape.php [root]

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

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
$stubdir = sys_get_temp_dir().'/zm_filter_widget_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
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
function html_input($name, $type='text', $value='', $options=array()) { return '<input name="'.htmlspecialchars($name).'" value="'.htmlspecialchars((string)$value).'"/>'; }
function htmlSelect($name, $contents, $values, $behaviours = false) { return '<select name="'.$name.'"></select>'; }

require_once $root.'/web/includes/Filter.php';

$Servers = array();

$bad = '"><b>x</b>';
function widget_html($term) {
  $filter = new Filter();
  $filter->addTerm($term);
  return $filter->simple_widget();
}

$html = widget_html(array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'Today', 'id'=>$bad, 'placeholder'=>$bad));
check('DateTime term id and placeholder are escaped', strpos($html, '<b>x</b>'), false);
$html = widget_html(array('attr'=>'StartDate', 'op'=>'>=', 'val'=>'Today', 'cookie'=>$bad));
check('Date term data-cookie is escaped', strpos($html, '<b>x</b>'), false);

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
