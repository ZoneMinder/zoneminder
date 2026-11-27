<?php
// view=monitor&mid=abc: validInt() leaves '' and views/js/monitor.js.php echoed it
// as "const mid=;", a syntax error that stopped the whole page script.
// Run: php tests/php/test_monitor_js_mid_numeric.php   (from the repo root)
$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/js/monitor.js.php');
$bad = preg_match_all('/echo\s+\'const mid=\'\s*\.\s*\$mid\s*\./', $src);
echo $bad ? "[FAIL] const mid is echoed without an (int) cast\n" : "[PASS] const mid is cast to int\n";
exit($bad ? 1 : 0);
