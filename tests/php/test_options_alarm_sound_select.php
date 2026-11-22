<?php
// ZM_WEB_ALARM_SOUND is chosen from the files in ZM_DIR_SOUNDS instead of typed (#3737).
// php tests/php/test_options_alarm_sound_select.php
$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/options.php');
$a = strpos($src, '$sound_files = array();');
$b = strpos($src, "\$configCats[\$tab]['ZM_WEB_HOMEVIEW']");
if ($a === false || $b === false) { echo "FAIL - sound select code not found\n"; exit(1); }
$dir = sys_get_temp_dir().'/zmsounds'.getmypid();
mkdir($dir); touch("$dir/snap.ogg"); touch("$dir/bell.mp3"); mkdir("$dir/sub");
define('ZM_DIR_SOUNDS', $dir);
$tab = 'web';
$configCats = array('web'=>array('ZM_WEB_ALARM_SOUND'=>array('Value'=>'gone.ogg')));
eval(substr($src, $a, $b - $a));
$h = $configCats['web']['ZM_WEB_ALARM_SOUND']['Hint'] ?? array();
$ok = isset($h['snap.ogg'], $h['bell.mp3'], $h['gone.ogg']) && !isset($h['sub']) && count($h) == 3;
echo ($ok ? 'ok' : 'FAIL')." - alarm sound hint lists directory files plus current value\n";
unlink("$dir/snap.ogg"); unlink("$dir/bell.mp3"); rmdir("$dir/sub"); rmdir($dir);
exit($ok ? 0 : 1);
