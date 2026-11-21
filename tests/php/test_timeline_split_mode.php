<?php
// The timeline's split mode (?view=timeline&mode=split) gives each monitor an activity
// row and an event row. timeline.php set the event row's position twice and never the
// activity row's, and the stylesheet read the positions from $char instead of $chart, so
// every row was printed as "top: px" with undefined variable warnings and all monitors
// were drawn on top of each other.
//
// php tests/php/test_timeline_split_mode.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// The split-mode layout block of timeline.php.
$src = file_get_contents($root.'/web/skins/classic/views/timeline.php');
$start = strpos($src, "} else if ( \$mode == 'split' ) {");
$end = strpos($src, "} else {\n  ZM\\Warning(\"No mode", $start);
$block = substr($src, $start + strlen("} else if ( \$mode == 'split' ) {"), $end - $start - strlen("} else if ( \$mode == 'split' ) {"));

$monitors = array(1 => array(), 2 => array());
$graphHeight = 200;
$chart = array(
  'width' => 800, 'height' => 600,
  'graph' => array('width' => 700, 'height' => 500, 'activityHeight' => 10),
  'data' => array('y' => array('range' => 100)),
  'image' => array('width' => 320, 'height' => 240),
);
eval($block);

check('each monitor gets an activity row', array_keys(isset($chart['activityBars']) ? $chart['activityBars'] : array()), array(1, 2));
check('the first activity row is at the top', isset($chart['activityBars'][1]['top']) ? $chart['activityBars'][1]['top'] : null, 0);
check('the event row sits below its activity row',
  $chart['eventBars'][1]['top'], $chart['graph']['activityBarHeight'] + 1);
check('the second monitor starts below the first',
  isset($chart['activityBars'][2]['top']) ? $chart['activityBars'][2]['top'] : null,
  $chart['eventBars'][1]['top'] + $chart['graph']['eventBarHeight'] + 1);

// The base stylesheet prints those positions.
$mode = 'split';
$max_aspect_ratio = 1;
$monEventSlots = array();
function render_css($file) {
  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
  ob_start();
  require $file;
  $out = ob_get_clean();
  restore_error_handler();
  return array($out, $warnings);
}
list($css, $warnings) = render_css($root.'/web/skins/classic/css/base/views/timeline.css.php');
$undefined = array_values(array_filter($warnings, function($w) { return strpos($w, 'char') !== false; }));
check('the stylesheet reads no undefined $char', $undefined, array());
check('no row is printed as "top: px"', strpos($css, 'top: px;'), false);
check('the second activity row has its position',
  strpos($css, 'top: '.$chart['activityBars'][2]['top'].'px;') !== false, true);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
