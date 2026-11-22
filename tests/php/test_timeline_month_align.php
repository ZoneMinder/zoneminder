<?php
// The timeline aligns its window to the major scale: whole days, whole hours, etc.
// For the 'month' and 'year' scales timeline.php used a fixed number of seconds
// (30 and 365 days), which is not a calendar boundary. A Mar 1 - Jun 30 2024 range
// was widened to 2024-02-17 .. 2024-07-15. The window must start on the first day
// of the month/year and end on the last day of the month/year.
//
// php tests/php/test_timeline_month_align.php [root]   (root defaults to the repo)

date_default_timezone_set('America/New_York');
define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
foreach (array('YEAR'=>'Y', 'MONTH'=>'M', 'WEEK'=>'d/m', 'DAY'=>'d', '4HOUR'=>'H:00', 'HOUR'=>'H:00', '10MINUTE'=>'H:i', 'MINUTE'=>'H:i', '10SECOND'=>'s', 'SECOND'=>'s') as $k=>$v)
  define('STRF_TL_AXIS_LABEL_'.$k, $v);
function translate($s) { return $s; }

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/skins/classic/includes/timeline_functions.php';

$src = file_get_contents($root.'/web/skins/classic/views/timeline.php');

preg_match('/\$scales = array\(.*?\n\);\n/s', $src, $m);
if (empty($m)) {
  echo "FAIL - could not find the \$scales block in timeline.php\n";
  exit(1);
}
$scalesSrc = $m[0];

// The scale-selection and alignment block of timeline.php.
$start = strpos($src, '// Adjust the range etc for scale');
$end = strpos($src, "if ( \$maxTimeT > time() )", $start);
if ($start === false || $end === false) {
  echo "FAIL - could not find the alignment block in timeline.php\n";
  exit(1);
}
$alignSrc = substr($src, $start, $end - $start);

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Same steps as timeline.php: pick the scale, then align the window to it.
function layout($minTime, $maxTime, $scalesSrc, $alignSrc) {
  eval($scalesSrc);
  $minTimeT = strtotime($minTime);
  $maxTimeT = strtotime($maxTime);
  $range = ($maxTimeT - $minTimeT) + 1;
  $majXScale = getDateScale($scales, $range, 4, 12);
  eval($alignSrc);
  return array(date(STRF_FMT_DATETIME_DB, $minTimeT), date(STRF_FMT_DATETIME_DB, $maxTimeT), $majXScale['name']);
}

// Month scale: a Mar 1 - Jun 30 2024 range is already on month boundaries.
list($lo, $hi, $scale) = layout('2024-03-01 00:00:00', '2024-06-30 23:59:59', $scalesSrc, $alignSrc);
check('the month scale is selected for a four month range', $scale, 'month');
check('a month range starts on the first of the month', $lo, '2024-03-01 00:00:00');
check('a month range ends on the last of the month', $hi, '2024-06-30 23:59:59');

// Month scale: mid-month times snap out to the enclosing calendar months.
list($lo, $hi, $scale) = layout('2024-03-05 10:00:00', '2024-07-05 15:00:00', $scalesSrc, $alignSrc);
check('a mid-month range still starts on the first of the month', $lo, '2024-03-01 00:00:00');
check('a mid-month range still ends on the last of the month', $hi, '2024-07-31 23:59:59');

// Year scale: whole calendar years, even though the input starts mid-year.
list($lo, $hi, $scale) = layout('2020-06-15 12:00:00', '2024-06-15 12:00:00', $scalesSrc, $alignSrc);
check('the year scale is selected for a four year range', $scale, 'year');
check('a year range starts on Jan 1', $lo, '2020-01-01 00:00:00');
check('a year range ends on Dec 31', $hi, '2024-12-31 23:59:59');

// Other scales keep the wall clock modulus alignment.
list($lo, $hi, $scale) = layout('2024-01-08 05:30:00', '2024-01-14 12:00:00', $scalesSrc, $alignSrc);
check('the day scale is still selected for a week long range', $scale, 'day');
check('a day range still starts at local midnight', $lo, '2024-01-08 00:00:00');
check('a day range still ends at the end of the local day', $hi, '2024-01-14 23:59:59');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
