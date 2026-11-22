<?php
// A monitor row with a null EncoderParameters (the column default, so any monitor
// added through the API or imported without it) made the edit page call
// explode("\n", null), which is deprecated in PHP 8.1+.
// Run: php tests/php/test_monitor_encoderparameters_null_explode.php   (from the repo root)
$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/monitor.php');
$bad = preg_match_all('/explode\(\s*"\\\\n",\s*\$monitor->EncoderParameters\(\)\s*\)/', $src);
echo $bad ? "[FAIL] $bad explode() call(s) pass \$monitor->EncoderParameters() unguarded\n" : "[PASS] EncoderParameters() is cast before explode\n";
exit($bad ? 1 : 0);
