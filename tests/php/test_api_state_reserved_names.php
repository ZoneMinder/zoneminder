<?php
// The API State model refuses the names zmpkg.pl treats as commands, as the web
// UI's state action already does (web/includes/actions/state.php): a state called
// "stop" could be saved but never applied through states/change/stop.
//
// Run as: php tests/php/test_api_state_reserved_names.php [repo root]

$root = $argv[1] ?? __DIR__.'/../..';
class AppModel {}
class App { static function uses() {} }
require $root.'/web/api/app/Model/State.php';

$model = new State();
$rule = $model->validate['Name']['notReserved']['rule'] ?? null;

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

check('Name has a reserved-name rule', is_array($rule) && $rule[0] === 'custom', true);
// CakePHP's custom rule passes when preg_match matches.
$allowed = function($name) use ($rule) { return is_array($rule) && preg_match($rule[1], $name) === 1; };
foreach (array('start', 'stop', 'restart', 'status', 'logrot', 'version') as $name) {
  check("'$name' is refused", $allowed($name), false);
}
foreach (array('Night', 'stopped', 'restart2', 'my start', 'Away') as $name) {
  check("'$name' is allowed", $allowed($name), true);
}

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
