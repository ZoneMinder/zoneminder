<?php
// Queries that list or count monitors for normal use must skip soft-deleted
// ones (Monitors.Deleted = 1). Usage: php test_deleted_monitors_excluded.php <root>
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
$fail = 0;
function check($name, $ok) {
  global $fail;
  echo ($ok ? 'PASS' : 'FAIL').': '.$name.PHP_EOL;
  if (!$ok) $fail++;
}
function src($p) { global $root; return file_get_contents($root.'/'.$p); }
// Return the line (statement) containing $needle.
function line_with($text, $needle) {
  foreach (explode("\n", $text) as $l) if (strpos($l, $needle) !== false) return $l;
  return '';
}
$probe = src('web/skins/classic/views/monitorprobe.php');
foreach (["FROM Monitors WHERE Type = 'Local'", "FROM `Monitors` WHERE `Type` = 'Remote'", "FROM `Monitors` WHERE `Type` = 'Ffmpeg'"] as $n) {
  check('monitorprobe.php: '.$n, (bool)preg_match('/Deleted`?\s*(=\s*0|=\s*false|!=\s*1)/i', line_with($probe, $n)));
}
$onvif = src('web/skins/classic/views/onvifprobe.php');
check('onvifprobe.php remote monitors', (bool)preg_match('/Deleted\s*(=\s*0|=\s*false|!=\s*1)/i', line_with($onvif, "Type='Remote'")));
$storage = src('web/includes/Storage.php');
check('Storage::MonitorCount', (bool)preg_match('/Deleted\s*(=\s*0|=\s*false|!=\s*1)/i', line_with($storage, 'AS MonitorCount FROM Monitors')));
$dns = src('web/skins/classic/views/_options_dnsmasq.php');
foreach (['Remote', 'Ffmpeg'] as $t) {
  check("_options_dnsmasq.php find Type=$t", (bool)preg_match("/Monitor::find\(\['Type'=>'$t',\s*'Deleted'=>(0|false)\]/", $dns));
}
exit($fail ? 1 : 0);
