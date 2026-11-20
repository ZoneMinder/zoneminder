<?php
// Prev/Next in the event view compared the sort value with plain > and =, which never match
// NULL. Sorted by End Time (or DiskSpace, Frames), an event still recording has a NULL sort
// value, so it got no Prev or Next at all and its neighbours skipped over it. MySQL sorts NULL
// before every other value; nearEventWhere() now treats NULL that way.
//
// Checked against MariaDB with literal rows while writing this; here the generated SQL and
// bind values are checked without a database.
// Run as: php tests/php/test_ajax_nearevents_null_sort.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/ajax/status.php');
if (!preg_match('/^function nearEventWhere\(.*?^\}/ms', $source, $m)) {
  echo "FAIL - web/ajax/status.php has no nearEventWhere()\n";
  exit(1);
}
eval($m[0]);

$col = 'E.EndDateTime';

// A NULL sort value is the smallest: every non-NULL row is after it, nothing is before it
// except NULL rows with a lower Id.
list($w, $v) = nearEventWhere($col, '>', '>', null, 7);
check('NULL value, after: any non-NULL row or a NULL row with a higher Id', $w, '(E.EndDateTime IS NOT NULL OR (E.EndDateTime <=> ? AND E.Id > ?))');
check('NULL value, after: binds NULL and the Id', $v, [null, 7]);
list($w, $v) = nearEventWhere($col, '<', '<', null, 7);
check('NULL value, before: only NULL rows with a lower Id', $w, '(0 OR (E.EndDateTime <=> ? AND E.Id < ?))');
check('NULL value, before: binds NULL and the Id', $v, [null, 7]);

// A non-NULL value has the NULL rows before it.
$t = '2026-01-01 10:00:00';
list($w, $v) = nearEventWhere($col, '>', '>', $t, 7);
check('value, after: larger values or a tie with a higher Id', $w, '(E.EndDateTime > ? OR (E.EndDateTime <=> ? AND E.Id > ?))');
check('value, after: binds value, value, Id', $v, [$t, $t, 7]);
list($w, $v) = nearEventWhere($col, '<', '<', $t, 7);
check('value, before: smaller values and NULL rows count as before', $w, '((E.EndDateTime < ? OR E.EndDateTime IS NULL) OR (E.EndDateTime <=> ? AND E.Id < ?))');
check('value, before: binds value, value, Id', $v, [$t, $t, 7]);

// Sorting by Id needs no tuple.
list($w, $v) = nearEventWhere('E.Id', '>', '>', 7, 7);
check('Id sort compares the Id alone', [$w, $v], ['E.Id > ?', [7]]);

// The placeholders and bind values must line up.
foreach ([[null, '>'], [null, '<'], [$t, '>'], [$t, '<']] as [$value, $cmp]) {
  list($w, $v) = nearEventWhere($col, $cmp, $cmp, $value, 7);
  check('placeholder count matches values ('.var_export($value, true)." $cmp)", substr_count($w, '?'), count($v));
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
