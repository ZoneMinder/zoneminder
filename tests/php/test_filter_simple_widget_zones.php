<?php
// The inline filter bar on the events and montage review pages renders an Alarmed
// Zone term through Filter::simple_widget(). It used $zones, which only
// widget() builds: PHP 8 raised "Undefined variable" and the select got no
// options, so the chosen zone could not be shown. simple_widget() now lists the
// zones of the monitors the user can see.
//
// Run as: php tests/php/test_filter_simple_widget_zones.php

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

$stubdir = sys_get_temp_dir().'/zm_filter_zones_stubs_'.getmypid();
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
  if (strpos($sql, 'FROM Zones') !== false) {
    return array(
      array('Id'=>'11', 'Name'=>'Driveway', 'MonitorId'=>'1', 'MonitorName'=>'Front'),
      array('Id'=>'12', 'Name'=>'Door', 'MonitorId'=>'1', 'MonitorName'=>'Front'),
      array('Id'=>'21', 'Name'=>'Hidden', 'MonitorId'=>'2', 'MonitorName'=>'Secret'),
    );
  }
  return array();
}
function visibleMonitor($id) { return $id != 2; }
function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function translate($s) { return $s; }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
function validHtmlStr($s) { return htmlspecialchars($s); }
function html_input($n, $t, $v) { return ""; }
$zonesGiven = 'not called';
function htmlSelect($name, $contents, $values, $behaviours = false) {
  global $zonesGiven;
  if (strpos($name, '[val]') !== false and is_array($behaviours) and isset($behaviours['multiple'])) $zonesGiven = $contents;
  return '<select name="'.$name.'"></select>';
}

require_once __DIR__.'/../../web/includes/Filter.php';

$Servers = array();

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

$filter = new Filter();
$filter->addTerm(array('attr'=>'AlarmedZoneId', 'op'=>'=', 'val'=>'11'));
$filter->simple_widget();

restore_error_handler();

check('rendering an Alarmed Zone term raises no PHP warning', $warnings, array());
check('the zone select lists the visible zones, labelled with their monitor', $zonesGiven, array('11'=>'Front: Driveway', '12'=>'Front: Door'));

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
