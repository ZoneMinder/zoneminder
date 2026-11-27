<?php
// Users.Language must hold every language code the user form offers (one per web/lang file).
$root = $argv[1] ?? dirname(__DIR__, 2);
$failures = 0;
function check($ok, $msg) { global $failures; echo ($ok ? 'ok' : 'FAIL').' - '.$msg."\n"; if (!$ok) $failures++; }

$create = file_get_contents("$root/db/zm_create.sql.in");
$users = substr($create, strpos($create, 'CREATE TABLE `Users`'));
$users = substr($users, 0, strpos($users, ';'));
check(preg_match('/`Language` varchar\((\d+)\)/', $users, $m) === 1, 'zm_create.sql.in declares Users.Language');
$width = (int)($m[1] ?? 0);

foreach (glob("$root/web/lang/*_*.php") as $file) {
  $code = basename($file, '.php');
  check(strlen($code) <= $width, "$code fits in Users.Language varchar($width)");
}

$widened = false;
foreach (glob("$root/db/zm_update-*.sql") as $update) {
  if (preg_match("/MODIFY `Language` varchar\($width\)/", file_get_contents($update))) $widened = true;
}
check($widened, "an update script widens existing databases to varchar($width)");

echo ($failures ? "$failures failed" : 'all passed')."\n";
exit($failures ? 1 : 0);
