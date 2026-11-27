<?php
// Zooming into a column of the timeline must give a window that ends where the
// column ends. timeline.php aligns the requested max time up to the end of the
// next scale step, so a max taken from the first pixel of the following column
// pulled in part of the next column.
date_default_timezone_set('UTC');
define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
foreach (array('YEAR'=>'Y', 'MONTH'=>'M', 'WEEK'=>'d/m', 'DAY'=>'d', '4HOUR'=>'H:00', 'HOUR'=>'H:00', '10MINUTE'=>'H:i', 'MINUTE'=>'H:i', '10SECOND'=>'s', 'SECOND'=>'s') as $k=>$v)
  define('STRF_TL_AXIS_LABEL_'.$k, $v);
function translate($s) { return $s; }
require_once __DIR__.'/../../web/skins/classic/includes/timeline_functions.php';

$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/timeline.php');
preg_match('/\$scales = array\(.*?\n\);\n/s', $src, $m);
$scalesSrc = $m[0];
preg_match('/function drawXGrid.*?\} # end function drawXGrid/s', $src, $m);
eval($m[0]);

// Same steps as timeline.php: pick the scale, then align the window to it.
function layout($minTime, $maxTime, $scalesSrc) {
  eval($scalesSrc);
  $minTimeT = strtotime($minTime);
  $maxTimeT = strtotime($maxTime);
  $range = ($maxTimeT - $minTimeT) + 1;
  $scale = getDateScale($scales, $range, 4, 12);
  $scaleStep = $scale['factor']*$scale['align'];
  $minTimeT -= ($minTimeT+date('Z', $minTimeT))%$scaleStep;
  $maxTimeT += ($scaleStep-($maxTimeT+date('Z', $maxTimeT))%$scaleStep)-1;
  return array($minTimeT, $maxTimeT, $scale);
}

$fail = 0;
foreach (array(
  array('seven days', '2024-03-04 00:00:00', '2024-03-10 23:59:59', 86400),
  array('four weeks', '2024-03-04 00:00:00', '2024-03-31 23:59:59', 86400),
) as $case) {
  list($name, $min, $max, $unit) = $case;
  list($lo, $hi, $scale) = layout($min, $max, $scalesSrc);
  $chart = array('graph'=>array('width'=>600),
    'data'=>array('x'=>array('lo'=>$lo, 'hi'=>$hi, 'density'=>($hi-$lo+1)/600)));
  $html = drawXGrid($chart, $scale, 'a', 'b', 'c', 'tlzoom');
  preg_match_all('/data-zoom-min-time="([^"]+)" data-zoom-max-time="([^"]+)"/', $html, $mm, PREG_SET_ORDER);
  $windows = array();
  foreach ($mm as $z) {
    list($a, $b) = layout($z[1], $z[2], $scalesSrc);
    $windows[] = array($a, $b);
  }
  $ok = count($windows) > 1;
  for ($k = 0; $k < count($windows)-1; $k++) {
    // One column must not run into the next one.
    if ($windows[$k][1] >= $windows[$k+1][0]) $ok = false;
  }
  if ($ok && end($windows)[1] > $hi) $ok = false;
  echo ($ok ? 'ok   ' : 'FAIL ')."$name: zoom windows stay inside their column\n";
  if (!$ok) {
    foreach ($windows as $w) echo '       '.date('Y-m-d H:i:s', $w[0]).' .. '.date('Y-m-d H:i:s', $w[1])."\n";
    $fail++;
  }
}
exit($fail ? 1 : 0);
