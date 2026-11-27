<?php
// Menu_Items stores MenuKey in varchar(32), Label in varchar(64), Icon in
// varchar(128) and Link in varchar(255), but the Options > Menu inputs had no
// maxlength, so a longer value was only refused by the database on save. Reads
// the view as text (it needs a full web environment to run) and checks each
// input carries its column length.
//
// Run as: php tests/php/test_menu_inputs_maxlength.php [repo root]

$root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
$text = file_get_contents($root.'/web/skins/classic/views/_options_menu.php');
$text = preg_replace('/<\?(?:php\b|=).*?\?>/s', 'PHP', $text);

$cases = array('MenuKey'=>32, 'Label'=>64, 'Link'=>255, 'Icon'=>128);
$passes = 0;
$failures = 0;
foreach ($cases as $field=>$want) {
  preg_match_all('/<input\b[^>]*name="items\[PHP\]\['.$field.'\]"[^>]*>/i', $text, $m);
  $ok = count($m[0]) === 1 && preg_match('/\bmaxlength\s*=\s*["\']?'.$want.'\b/', $m[0][0]);
  if ($ok) $passes++; else $failures++;
  echo ($ok ? 'ok' : 'FAIL')." - menu item $field input has maxlength $want\n";
}
echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
