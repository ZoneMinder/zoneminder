<?php
// User_Roles.Name is varchar(64), but the role form let a browser submit a longer
// name, which the save then failed on. Reads the view as text (it needs a full
// web environment to run) and checks the Name input carries the column length.
//
// Run as: php tests/php/test_role_name_maxlength.php [repo root]

$root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
$file = 'web/skins/classic/views/role.php';
$text = file_get_contents($root.'/'.$file);
$text = preg_replace('/<\?(?:php\b|=).*?\?>/s', 'PHP', $text);

preg_match_all('/<input\b[^>]*name="role\[Name\]"[^>]*>/i', $text, $m);
$ok = count($m[0]) === 1 && preg_match('/\bmaxlength\s*=\s*["\']?64\b/', $m[0][0]);
echo ($ok ? 'ok' : 'FAIL')." - role name input has maxlength 64\n";
echo $ok ? "\n1 passed, 0 failed\n" : "\n0 passed, 1 failed\n";
exit($ok ? 0 : 1);
