<?php
// The Report chart keyed its lines by "Mon day" only, so events on the same calendar day of
// different years were summed into one line: Nov 21 2025 (600s at 10:xx) and Nov 21 2026
// (300s at 10:xx) gave a single "Nov 21" line with 900 at 10:00. The year is now in the key.
//
// Run as: php tests/php/test_report_chart_year_buckets.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$view = file_get_contents($root.'/web/skins/classic/views/report.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Pull the bucketing code out of the view and run it on two events a year apart.
if (!preg_match('/^(time_labels = Array\(\);.*?)^\/\*\nfor/ms', $view, $m)) {
  check('bucketing code found in the view', false, true);
  exit(1);
}
$js = 'var events = [
  {StartDateTime: "2025-11-21 10:05:00", Length: "600"},
  {StartDateTime: "2026-11-21 10:30:00", Length: "300"}];
'.$m[1].'
console.log(JSON.stringify(datasets.map(d => [d.label, d.data[10]])));';
$f = tempnam(sys_get_temp_dir(), 'zmchart');
file_put_contents($f, $js);
$out = trim(shell_exec('node '.escapeshellarg($f).' 2>&1'));
unlink($f);
$rows = json_decode($out, true);

check('two years of the same calendar day make two lines', is_array($rows) ? count($rows) : $out, 2);
check('each line holds only its own year', is_array($rows) ? array_column($rows, 1) : null, array(600, 300));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
