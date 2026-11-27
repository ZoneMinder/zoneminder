<?php
// Regression test for monitorStatusFilterSql() in views/_monitor_filters.php.
//
// Deleted monitors are hidden from every listing, so the Status filter carries
// a 'Deleted' pseudo status as the only way to see them again. That one
// function decides both halves - which statuses match, and whether deleted
// monitors are in or out - for the console page, the console ajax endpoint and
// getFilteredMonitorIds(). Getting it wrong either hides deleted monitors for
// good or leaks them into every listing.
//
// The function is pulled out of the view rather than executed through it: the
// view needs a session, a db and a skin. What is worth pinning down is the sql
// it builds and the bind values that go with it.
//
// Run as: php tests/php/test_monitor_status_filter.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

// Pull just the function out of the view, which cannot be included directly.
$path = __DIR__ . '/../../web/skins/classic/views/_monitor_filters.php';
$src = file_get_contents($path);
if ($src === false) {
  echo "Cannot read $path\n";
  exit(1);
}
if (!preg_match('/^function monitorStatusFilterSql\(.*?\n\}/ms', $src, $m)) {
  echo "Could not find monitorStatusFilterSql() in $path\n";
  exit(1);
}
eval($m[0]);

const COL = "COALESCE(`Status`, 'NotRunning')";

// No Status filter at all: unchanged behaviour, live monitors only. The filter
// arrives as '' from getFilterSelection(), or as array() from an emptied
// multi-select.
echo "no status selected\n";
foreach (array(array('', "''"), array(array(), 'array()'), array(null, 'null')) as $case) {
  $values = array();
  check('live monitors only for '.$case[1],
    monitorStatusFilterSql($case[0], $values, COL), 'M.`Deleted`=false');
  check('no bind values for '.$case[1], $values, array());
}

// Real statuses only: live monitors, matching those statuses.
echo "\nreal statuses only\n";
$values = array();
check('single status',
  monitorStatusFilterSql(array('Running'), $values, COL),
  'M.`Deleted`=false AND '.COL.' IN (?)');
check('single status binds it', $values, array('Running'));

$values = array();
check('two statuses',
  monitorStatusFilterSql(array('Running', 'Connected'), $values, COL),
  'M.`Deleted`=false AND '.COL.' IN (?,?)');
check('two statuses bind in order', $values, array('Running', 'Connected'));

// A non-array value: getFilterSelection() can return a bare string from a
// cookie written before the filter became a multi-select.
$values = array();
check('bare string status',
  monitorStatusFilterSql('Running', $values, COL),
  'M.`Deleted`=false AND '.COL.' IN (?)');
check('bare string binds it', $values, array('Running'));

// Deleted on its own: only the deleted monitors, and no status match at all -
// their Monitor_Status row is stale, so matching on it would drop them.
echo "\ndeleted on its own\n";
$values = array();
check('deleted only',
  monitorStatusFilterSql(array('Deleted'), $values, COL), 'M.`Deleted`=true');
check('deleted only binds nothing', $values, array());

$values = array();
check('bare string deleted',
  monitorStatusFilterSql('Deleted', $values, COL), 'M.`Deleted`=true');

// Deleted alongside real statuses: adds the deleted monitors to that selection
// rather than intersecting with it, which would always be empty.
echo "\ndeleted with real statuses\n";
$values = array();
check('deleted plus a status',
  monitorStatusFilterSql(array('Running', 'Deleted'), $values, COL),
  '(M.`Deleted`=false AND '.COL.' IN (?) OR M.`Deleted`=true)');
check('deleted is not bound as a status', $values, array('Running'));

$values = array();
check('order of selection does not matter',
  monitorStatusFilterSql(array('Deleted', 'Running'), $values, COL),
  '(M.`Deleted`=false AND '.COL.' IN (?) OR M.`Deleted`=true)');
check('still only the real status is bound', $values, array('Running'));

// The status column expression differs between callers (the ajax endpoint
// coalesces WebSite monitors to Running), so it must come from the caller.
echo "\ncaller supplies the status column\n";
$values = array();
$other = 'COALESCE(S.Status, IF(M.Type="WebSite","Running","NotRunning"))';
check('column is used verbatim',
  monitorStatusFilterSql(array('Running'), $values, $other),
  'M.`Deleted`=false AND '.$other.' IN (?)');

// Values must append, not replace: callers pass in the bind values collected
// from the filters before this one.
echo "\nappends to existing bind values\n";
$values = array(7, 'Always');
check('existing values are kept',
  monitorStatusFilterSql(array('Running'), $values, COL),
  'M.`Deleted`=false AND '.COL.' IN (?)');
check('new value appended after them', $values, array(7, 'Always', 'Running'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
