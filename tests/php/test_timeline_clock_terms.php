<?php
// Current DateTime, Date, Time and Weekday terms test the clock, not the
// event, so the timeline must keep them in its links and must not take their
// values as the start or end of the window.
namespace ZM {
  function Debug($s) {}
  function Warning($s) {}
  function Error($s) {}
  function Info($s) {}
}

namespace {
$stubdir = sys_get_temp_dir().'/zm_timeline_clock_terms_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

date_default_timezone_set('UTC');
define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function translate($s) { return $s; }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

require_once __DIR__.'/../../web/includes/Filter.php';
require_once __DIR__.'/../../web/skins/classic/includes/timeline_functions.php';

$monitor = array('attr'=>'MonitorId', 'op'=>'=', 'val'=>'1');
$fail = 0;
foreach (array(
  array('current time', array($monitor,
    array('cnj'=>'and', 'attr'=>'CurrentTime', 'op'=>'>=', 'val'=>'08:00'),
    array('cnj'=>'and', 'attr'=>'CurrentTime', 'op'=>'<=', 'val'=>'18:00')),
    'MonitorId CurrentTime CurrentTime', '', ''),
  array('current datetime', array($monitor,
    array('cnj'=>'and', 'attr'=>'CurrentDateTime', 'op'=>'>=', 'val'=>'2026-12-01 00:00:00')),
    'MonitorId CurrentDateTime', '', ''),
  array('current date', array($monitor,
    array('cnj'=>'and', 'attr'=>'CurrentDate', 'op'=>'<=', 'val'=>'2026-01-01')),
    'MonitorId CurrentDate', '', ''),
  array('current weekday', array($monitor,
    array('cnj'=>'and', 'attr'=>'CurrentWeekday', 'op'=>'<', 'val'=>'5')),
    'MonitorId CurrentWeekday', '', ''),
  array('start date time still bounds the window', array($monitor,
    array('cnj'=>'and', 'attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'2024-03-04 00:00:00'),
    array('cnj'=>'and', 'attr'=>'CurrentTime', 'op'=>'>=', 'val'=>'08:00')),
    'MonitorId CurrentTime', '2024-03-04 00:00:00', ''),
) as $case) {
  list($name, $terms, $wantAttrs, $wantMin, $wantMax) = $case;
  $filter = new ZM\Filter();
  $filter->Query(array('terms'=>$terms));
  $tree = $filter->tree();
  $minTime = $maxTime = $expandable = false;
  extractDatetimeRange($tree, $minTime, $maxTime, $expandable);
  $attrs = array();
  foreach (parseTreeToFilter($tree)['Query']['terms'] as $term) {
    if (isset($term['attr'])) $attrs[] = $term['attr'];
  }
  $attrs = implode(' ', $attrs);
  $ok = ($attrs === $wantAttrs && $minTime === $wantMin && $maxTime === $wantMax);
  echo ($ok ? 'ok   ' : 'FAIL ')."$name: terms '$attrs', window '$minTime' .. '$maxTime'\n";
  if (!$ok) $fail++;
}

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);
exit($fail ? 1 : 0);
}
