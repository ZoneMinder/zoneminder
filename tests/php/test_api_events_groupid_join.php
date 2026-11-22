<?php
/**
 * Checks the EventsController::index() decision that adds the Groups_Monitors
 * join. buildFilter() turns "GroupId >:1" into the key 'GroupId >', so the
 * guard must look for GroupId inside condition keys (including nested arrays),
 * not just for a bare 'GroupId' key. The guard is read straight out of the
 * controller and evaluated, so this test works for both the fixed and the
 * original source.
 *
 * Usage: php tests/php/test_api_events_groupid_join.php [repo-root]
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : dirname(__DIR__, 2);
$controller = $root . '/web/api/app/Controller/EventsController.php';

$source = @file_get_contents($controller);
if ($source === false) {
  fwrite(STDERR, "cannot read $controller\n");
  exit(2);
}

$contain_marker = '$settings[\'contain\'] = [];';
$join_marker = '$settings[\'joins\'] = array(';

$contain_pos = strpos($source, $contain_marker);
if ($contain_pos === false) {
  fwrite(STDERR, "could not find contain reset in $controller\n");
  exit(2);
}
$join_pos = strpos($source, $join_marker, $contain_pos + strlen($contain_marker));
if ($join_pos === false) {
  fwrite(STDERR, "could not find Groups_Monitors join in $controller\n");
  exit(2);
}

$region = substr(
  $source,
  $contain_pos + strlen($contain_marker),
  $join_pos - ($contain_pos + strlen($contain_marker))
);
$region_trimmed = rtrim($region);

# The guard is the last "if (...) {" in the region; everything before it is
# helper code (e.g. the $references_group closure) that the guard may call.
if (!preg_match('/\bif\s*\((.*)\)\s*\{\s*$/', $region_trimmed, $m, PREG_OFFSET_CAPTURE)) {
  fwrite(STDERR, "could not locate the join guard in $controller\n");
  exit(2);
}
$condition_expr = trim($m[1][0]);
$helper_code = substr($region_trimmed, 0, $m[0][1]);

$eval_code = $helper_code . "\nreturn (" . $condition_expr . ");\n";

$adds_join = function (array $conditions) use ($eval_code) {
  return eval($eval_code);
};

$failures = 0;

function check_join($label, $actual, $expected, &$failures) {
  if ($actual === $expected) {
    echo "ok - $label\n";
  } else {
    $failures++;
    echo "FAIL - $label: expected " . var_export($expected, true)
      . ", got " . var_export($actual, true) . "\n";
  }
}

check_join("bare GroupId", $adds_join(array('GroupId' => '1')), true, $failures);
check_join("GroupId >", $adds_join(array('GroupId >' => '1')), true, $failures);
check_join("GroupId !=", $adds_join(array('GroupId !=' => '2')), true, $failures);
check_join(
  "OR nested GroupId",
  $adds_join(array('OR' => array('GroupId' => '1', 'Event.MonitorId' => '3'))),
  true,
  $failures
);
check_join("unrelated MonitorId", $adds_join(array('Event.MonitorId' => '3')), false, $failures);

echo $failures === 0
  ? "PASS: join is added whenever GroupId appears, including operators and nested arrays\n"
  : "FAILED: $failures assertion(s) failed\n";

exit($failures === 0 ? 0 : 1);
