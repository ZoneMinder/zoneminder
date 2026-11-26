<?php
// Source check for EventsController::index(). Without named URL parameters it
// filters on $_REQUEST, which also holds the legacy API credentials
// (?user=&pass=, ?username=&password=, ?auth=, ?stateful=). Each of those was
// rejected as "Unknown filter field: user", so /events.json?user=..&pass=.. was
// a 400 for every client authenticating that way.
//
// Run: php tests/php/test_api_events_index_auth_params.php   (from the repo root)

$code = file_get_contents(__DIR__.'/../../web/api/app/Controller/EventsController.php');
preg_match('/\$raw_params = \$_REQUEST;(.*?)buildFilter\(\$raw_params/s', $code, $m);
$between = isset($m[1]) ? $m[1] : '';

$failures = 0; $passes = 0;
function check($name, $condition) {
  global $failures, $passes;
  if ($condition) { $passes++; echo "ok - $name\n"; } else { $failures++; echo "not ok - $name\n"; }
}

check('index() filters on $_REQUEST when there are no named params', $between !== '');
foreach (array('user', 'pass', 'username', 'password', 'auth', 'stateful') as $credential) {
  check("$credential is dropped before buildFilter()",
    preg_match("/unset\(\\\$raw_params\[\\\$credential\]\)/", $between) === 1 && strpos($between, "'$credential'") !== false);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
