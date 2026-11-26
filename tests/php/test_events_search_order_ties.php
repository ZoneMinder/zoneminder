<?php
// The Events list pages the search / advanced search results in PHP (array_slice) after
// fetching them sorted by the chosen column only. Events with the same value in that column
// (same Length, Frames, Cause...) can come back in a different order on each page request,
// so they repeat on one page and are missing from another. The paged-in-SQL path already
// orders ties by E.Id; the search path now does too.
//
// Run as: php tests/php/test_events_search_order_ties.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/ajax/events.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Every SELECT that builds the list of events groups by E.Id and must end its ORDER BY
// with E.Id. Collect the PHP expression that follows each "GROUP BY E.Id" up to its ';'.
preg_match_all('/GROUP BY E\.Id[^;]*;/s', $source, $m);
check('found the event list queries', count($m[0]) >= 3, true);
foreach ($m[0] as $i => $stmt) {
  $has_order = (strpos($stmt, 'ORDER BY') !== false);
  if (!$has_order) continue;
  check("query ".($i + 1)." orders ties by E.Id", (bool)preg_match("/'E\\.Id'\s*;\s*$/", $stmt) || (bool)preg_match('/\$order_by/', $stmt), true);
  check("query ".($i + 1)." has no ORDER BY without the tiebreak", (bool)preg_match("/\?\s*' ORDER BY '\s*\.\s*\\\$sort\s*:\s*''/", $stmt), false);
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
