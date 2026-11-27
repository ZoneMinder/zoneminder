<?php
// The monitor form let a value through that its Monitors column cannot store:
// text inputs had no maxlength for their varchar(N) column, and number inputs
// for tinyint/smallint columns had no max. Saving then failed with a database
// error ("Data too long", "Out of range value") instead of the browser saying
// so. Each input now carries the limit of its column.
//
// Run as: php tests/php/test_monitor_form_limits.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$schema = file_get_contents($root.'/db/zm_create.sql.in');
$view = file_get_contents($root.'/web/skins/classic/views/monitor.php');

preg_match('/CREATE TABLE `Monitors` \((.*?)\n\)/s', $schema, $m);
$columns = array();
foreach (explode("\n", $m[1]) as $line) {
  if (preg_match('/^\s*`(\w+)`\s+(.+?),?\s*$/', $line, $c)) $columns[$c[1]] = $c[2];
}

$failures = 0;
$checked = 0;
foreach (explode("\n", $view) as $n => $line) {
  if (!preg_match('/<input\b.*name="newMonitor\[(\w+)\]"/', $line, $im)) continue;
  $col = $im[1];
  if (!isset($columns[$col])) continue;
  $type = preg_match('/type="(\w+)"/', $line, $t) ? $t[1] : 'text';
  $def = $columns[$col];
  if (in_array($type, array('text', 'password', 'url')) and preg_match('/^varchar\((\d+)\)/i', $def, $v)) {
    $checked++;
    if (!preg_match('/maxlength="(\d+)"/', $line, $ml) or $ml[1] > $v[1]) {
      $failures++;
      echo 'FAIL - line '.($n+1)." $col: varchar({$v[1]}) but maxlength ".($ml[1] ?? 'none')."\n";
    }
  } else if ($type == 'number' and preg_match('/^(tiny|small)int\b/i', $def, $i)) {
    $checked++;
    $unsigned = stripos($def, 'unsigned') !== false;
    $limit = array('tiny' => $unsigned ? 255 : 127, 'small' => $unsigned ? 65535 : 32767)[strtolower($i[1])];
    if (!preg_match('/\bmax="(\d+)"/', $line, $mx) or $mx[1] > $limit) {
      $failures++;
      echo 'FAIL - line '.($n+1)." $col: $def but max ".($mx[1] ?? 'none')."\n";
    }
  }
}
echo "checked $checked inputs\n";
echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
