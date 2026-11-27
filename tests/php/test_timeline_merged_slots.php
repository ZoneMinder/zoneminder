<?php
// Neighbouring slots of different events are merged into one bar when narrower than
// $minEventWidth. The merged bar must remember which event owns each slot so a click
// or hover can open the one under the pointer, not always the leftmost.
//
// php tests/php/test_timeline_merged_slots.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/skins/classic/includes/timeline_functions.php';

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$src = file_get_contents($root.'/web/skins/classic/views/timeline.php');
$start = strpos($src, '// Optimise boxes');
$end = strpos($src, '//print_r( $monEventSlots );', $start);
$block = substr($src, $start, $end - $start);

function slot($id) {
  return array('count'=>0, 'width'=>1, 'offset'=>1, 'event'=>array('Id'=>$id, 'MonitorId'=>1));
}
$minEventWidth = 3;
$maxEventWidth = 20;
$chart = array('graph'=>array('width'=>10));
$monEventSlots = array(1 => array(0=>slot(10), 1=>slot(11), 2=>slot(12), 5=>slot(20), 6=>slot(20)));
eval($block);
$s = $monEventSlots[1];

check('three small events become one bar', array_keys($s), array(0, 5));
check('bar is three slots wide', $s[0]['width'], 3);
check('bar keeps the first event for the default target', $s[0]['event']['Id'], 10);
$ids = isset($s[0]['units']) ? array_map(function($u) { return $u[0]; }, $s[0]['units']) : null;
check('bar records the event under each slot', $ids, array(10, 11, 12));
check('same-event merge needs no per-slot list', isset($s[5]['units']), false);
check('same-event merge widens the bar', $s[5]['width'], 2);
exit($failures ? 1 : 0);
