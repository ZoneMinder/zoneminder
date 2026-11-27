<?php
// An unparsable date/time value must match no events instead of 1970-01-01.
//
// Run as: php tests/php/test_filter_term_bad_date.php

namespace ZM;

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function Warning($s) {}
function Error($s)   {}
function Debug($s)   {}
function translate($s) { return $s; }
if (!class_exists('\ZM\Group')) {
  class Group {
    public function __construct($v=null) {}
    public function MonitorIds() { return array(); }
  }
}

require_once __DIR__.'/../../web/includes/FilterTerm.php';

$failures = 0;
function check($name, $ok, $sql) {
  global $failures;
  if ($ok) { echo "ok - $name\n"; return; }
  $failures++;
  echo "FAIL - $name\n  SQL: $sql\n";
}

foreach (array('StartDateTime', 'EndDateTime', 'DateTime', 'StartDate', 'Date', 'EndDate', 'StartTime', 'Time') as $attr) {
  foreach (array('garbage', '2026-13-45') as $bad) {
    $sql = (new FilterTerm(null, array('attr'=>$attr, 'op'=>'>=', 'val'=>$bad), 0))->sql();
    check("$attr >= '$bad' matches nothing", strpos($sql, '1=0') !== false && strpos($sql, '1970') === false, $sql);
  }
}

$sql = (new FilterTerm(null, array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'garbage', 'cnj'=>'and', 'obr'=>1, 'cbr'=>1), 1))->sql();
check('brackets stay balanced', substr_count($sql, '(') === substr_count($sql, ')') && strpos($sql, 'and') !== false, $sql);

$sql = (new FilterTerm(null, array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'2026-05-06 09:42:56'), 0))->sql();
check('absolute date unchanged', strpos($sql, "E.StartDateTime >= '2026-05-06 09:42:56'") !== false, $sql);
$sql = (new FilterTerm(null, array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>'-1 day'), 0))->sql();
check('relative date unchanged', strpos($sql, '1=0') === false && strpos($sql, 'E.StartDateTime >= \'') !== false, $sql);
$sql = (new FilterTerm(null, array('attr'=>'EndDateTime', 'op'=>'IS', 'val'=>'NULL'), 0))->sql();
check('NULL unchanged', strpos($sql, 'IS NULL') !== false, $sql);
$sql = (new FilterTerm(null, array('attr'=>'StartDateTime', 'op'=>'<=', 'val'=>'now'), 0))->sql();
check('now unchanged', strpos($sql, '1=0') === false, $sql);

exit($failures ? 1 : 0);
