<?php
// Security regression test: a request event id must not be taken as a forged
// database row. ZM\ZM_Object::__construct() treats an array argument as the
// record's own fields, so view_video.php (eid) and ajax/events.php
// archive/delete (eids[]) handing a request array to new ZM\Event() let the
// caller choose the MonitorId that canView()/canEdit() checks and the
// StorageId/Scheme/Id that Path() is built from -- an arbitrary file read, and
// an rm -rf / archive of events on a denied monitor. refs GHSA-6h8f-gx4m-qqw4.
//
// 1. validInt()/validCardinal() give '' for a non-scalar (so a raw array never
//    survives into new ZM\Event()).
// 2. getAffectedIds() drops any element that is itself an array.
// 3. view_video.php routes eid/event_id through validInt(), and
//    ajax/events.php archiveRequest()/deleteRequest() route the id through
//    validCardinal(), before constructing the event.
//
// Run as: php tests/php/test_event_id_array_row.php

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

$web = __DIR__.'/../../web';
$functions = file_get_contents($web.'/includes/functions.php');

// Lift the three pure-ish functions from functions.php and define them here.
foreach (array('validInt', 'validCardinal', 'getAffectedIds') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $functions, $m)) {
    echo "FAIL - could not find $fn in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}

// 1. The validators reject non-scalars.
check('validInt of a number', validInt('42'), '42');
check('validInt of an array is empty', validInt(array('Id' => '9', 'MonitorId' => '1')), '');
check('validCardinal of a number', validCardinal('42'), '42');
check('validCardinal of an array is empty', validCardinal(array('Id' => '9', 'MonitorId' => '1')), '');
check('validCardinal of a nested array is empty', validCardinal(array(array('9'))), '');

// 2. getAffectedIds drops array elements and keeps plain ids.
$_REQUEST = array('eids' => array('5', array('Id' => '9', 'MonitorId' => '1'), 'x7'));
check('getAffectedIds keeps plain ids and drops the forged-row element',
  getAffectedIds('eid'), array('5', '7'));
// A flat array is a legitimate multi-select (eid[]=9&eid[]=1); every element is
// still coerced to a scalar id, so no element can be a forged row.
$_REQUEST = array('eids' => array(array('Id' => '9', 'MonitorId' => '1')));
check('getAffectedIds drops a lone forged-row element', getAffectedIds('eid'), array());
$_REQUEST = array('eid' => '12');
check('getAffectedIds keeps a single plain id', getAffectedIds('eid'), array('12'));

// 3. The two sinks route the request id through a validator before new ZM\Event.
$vv = file_get_contents($web.'/views/view_video.php');
check('view_video.php validInts eid before building the event',
  (bool)preg_match('/validInt\(\s*\$_REQUEST\[\s*[\'"]eid[\'"]\s*\]\s*\)/', $vv), true);
check('view_video.php validInts event_id before building the event',
  (bool)preg_match('/validInt\(\s*\$_REQUEST\[\s*[\'"]event_id[\'"]\s*\]\s*\)/', $vv), true);

$events = file_get_contents($web.'/ajax/events.php');
// archiveRequest() and deleteRequest() each reduce $eid with validCardinal()
// before new ZM\Event($eid). Check each function body in turn.
foreach (array('archiveRequest', 'deleteRequest') as $fn) {
  if (!preg_match('/function '.$fn.'\([^)]*\)\s*\{(.*?)\n\}/s', $events, $m)) {
    check("$fn body found in events.php", false, true);
    continue;
  }
  $body = $m[1];
  $vc = strpos($body, 'validCardinal($eid)');
  $ev = strpos($body, 'new ZM\\Event($eid)');
  check("$fn reduces \$eid with validCardinal before new ZM\\Event",
    ($vc !== false and $ev !== false and $vc < $ev), true);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
