<?php
date_default_timezone_set('UTC');
define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');

function validInt($v) {
  return is_numeric($v);
}

$failed = 0;
function check($cond, $msg) {
  global $failed;
  if ( !$cond ) {
    echo "FAIL: $msg\n";
    $failed++;
  }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/skins/classic/views/timeline.php');

$block = null;
if ( preg_match('/if \( isset\(\$range\) and validInt\(\$range\) \) \{.*?\n\}\n\n\$tree = false;/s', $src, $m) ) {
  $pos = strpos($m[0], '$tree = false;');
  $block = ($pos === false) ? $m[0] : substr($m[0], 0, $pos);
} else {
  check(false, 'range block not found in timeline.php');
}

if ( $block !== null ) {
  // Case 1: pan right at the present must stay clamped to the present and keep its length.
  unset($minTime, $maxTime, $midTime, $midTimeT, $minTimeT, $maxTimeT, $shiftT);
  $range = 3600;
  $midTime = date('Y-m-d H:i:s', time());
  eval($block);
  $now = time();
  check($maxTimeT <= $now, "maxTimeT ($maxTimeT) must not exceed now ($now)");
  check(($maxTimeT - $minTimeT) + 1 == 3600, 'window length must remain 3600 after clamping, got '.((($maxTimeT - $minTimeT) + 1)));

  // Case 2: a window wholly in the past must be left untouched.
  unset($minTime, $maxTime, $midTime, $midTimeT, $minTimeT, $maxTimeT, $shiftT);
  $range = 3600;
  $midTime = date('Y-m-d H:i:s', time() - 86400);
  eval($block);
  check(($maxTimeT - $minTimeT) + 1 == 3600, 'past window length must remain 3600, got '.((($maxTimeT - $minTimeT) + 1)));
  check($midTimeT == strtotime($midTime), 'past window midTimeT must be unchanged');
}

if ( $failed > 0 ) {
  exit(1);
}

echo "PASS\n";
exit(0);
