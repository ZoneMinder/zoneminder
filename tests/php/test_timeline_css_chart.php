<?php
// The timeline's per-css stylesheets are required from inside
// xhtmlHeadersStart(), so the view's $chart only reaches them through a
// global statement. Without one the classic css printed "width: px" and an
// undefined variable warning for every size.
//
// php tests/php/test_timeline_css_chart.php   (from the repo root)

$root = realpath(__DIR__.'/../..');

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

$chart = array(
  'width' => 800, 'height' => 600,
  'graph' => array('width' => 700, 'height' => 500, 'activityHeight' => 10, 'eventBarHeight' => 20),
  'image' => array('width' => 320, 'height' => 240),
);

// Required the way xhtmlHeadersStart() does it: from inside a function.
function render_css($file) {
  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
  ob_start();
  require $file;
  $out = ob_get_clean();
  restore_error_handler();
  return array($out, $warnings);
}

foreach (glob($root.'/web/skins/classic/css/*/views/timeline.css.php') as $file) {
  if (!filesize($file) or strpos($file, '/css/base/') !== false) continue;
  $name = basename(dirname(dirname($file)));
  list($out, $warnings) = render_css($file);
  check("$name: no warnings", $warnings, array());
  check("$name: chart size is printed", strpos($out, 'width: 800px;') !== false, true);
  check("$name: no empty sizes", strpos($out, ': px;'), false);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
