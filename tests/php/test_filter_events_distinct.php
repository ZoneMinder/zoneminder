<?php
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures; $tests++;
  if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, "function ".$name."(");
  if ($sig === false) return null;
  $brace = strpos($src, "{", $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === "{") $depth++;
    elseif ($src[$i] === "}") { $depth--; if ($depth === 0) return substr($src, $brace, $i - $brace + 1); }
  }
  return null;
}

/* Filter::Events() LEFT JOINs Events_Tags/Tags, so an event with several tags
 * comes back once per tag, and a SQL LIMIT counts those duplicate rows. The
 * query must collapse to one row per event. Structural test.
 * The SQL was checked by hand on MariaDB 11.4 with and without ONLY_FULL_GROUP_BY. */
$src = file_get_contents(dirname(__DIR__, 2).'/web/includes/Filter.php');
$body = extract_function($src, 'Events');
check('Events() found', $body !== null);
$body = $body ?? '';
check('tag join still present for tag filtering', strpos($body, 'LEFT JOIN Events_Tags') !== false);
check('query selects distinct event rows', preg_match('/DISTINCT\s+E\.\*/i', $body) === 1);
// GROUP BY E.Id with E.* and M.Name is rejected by MariaDB under ONLY_FULL_GROUP_BY.
check('no GROUP BY (breaks ONLY_FULL_GROUP_BY on MariaDB)', stripos($body, 'GROUP BY') === false);
echo "\n$tests checks, $failures failed\n";
exit($failures === 0 ? 0 : 1);

