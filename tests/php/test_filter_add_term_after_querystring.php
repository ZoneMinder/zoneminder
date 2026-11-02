<?php
// A term added after querystring() was called must show up in the next
// querystring(), also on a clone (the event audit report builds one link per
// monitor from a shared filter).
//
// Run as: php tests/php/test_filter_add_term_after_querystring.php

namespace ZM;

$failures = 0;
$passes = 0;

function check($name, $ok) {
  global $failures, $passes;
  if ($ok) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_filter_add_term_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }
function translate($s) { return $s; }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

require_once __DIR__.'/../../web/includes/Filter.php';

$filter = new Filter();
$filter->addTerm(array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'2026-08-09 00:00:00'));
$filter->querystring();

$filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'=', 'val'=>'64'));
check('a term added after querystring() is in the next one',
  strpos(urldecode($filter->querystring()), '[val]=64') !== false);

$base = new Filter();
$base->addTerm(array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'2026-08-09 00:00:00'));
$base->querystring();
$first = (clone $base)->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'=', 'val'=>'45'))->querystring();
$second = (clone $base)->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'=', 'val'=>'64'))->querystring();
check('a clone gets its own monitor term', strpos(urldecode($first), '[val]=45') !== false);
check('a second clone does not keep the first monitor term',
  strpos(urldecode($second), '[val]=64') !== false && strpos(urldecode($second), '[val]=45') === false);

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/Monitor.php');

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
