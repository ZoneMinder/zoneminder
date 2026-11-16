<?php
// A new monitor has a null Options, and preg_split(null) is deprecated in PHP 8.1+.
// Run: php tests/php/test_monitor_options_null_split.php   (from the repo root)
$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/monitor.php');
$bad = preg_match_all('/preg_split\(\s*\'[^\']*\',\s*\$monitor->Options\(\)\s*\)/', $src);
echo $bad ? "[FAIL] $bad preg_split() call(s) pass \$monitor->Options() unguarded\n" : "[PASS] Options() is cast before preg_split\n";
exit($bad ? 1 : 0);
