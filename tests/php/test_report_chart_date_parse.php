<?php
// The Report view's chart built each event's date with new Date(event.StartDateTime), where
// StartDateTime is MySQL's "YYYY-MM-DD HH:MM:SS". That string is not a format Date() has to
// accept: Safari returns Invalid Date, so every event landed in a dataset labelled
// "undefined NaN" at hour NaN and the chart was empty. It now passes the ISO form with a T.
//
// Run as: php tests/php/test_report_chart_date_parse.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$view = file_get_contents($root.'/web/skins/classic/views/report.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

check('the chart does not hand the raw MySQL datetime to Date()',
  (bool)preg_match('/new Date\(\s*event\.StartDateTime\s*\)/', $view), false);
check('the chart converts StartDateTime to ISO form before parsing',
  (bool)preg_match("/new Date\(String\(event\.StartDateTime\)\.replace\(' ', 'T'\)\)/", $view), true);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
