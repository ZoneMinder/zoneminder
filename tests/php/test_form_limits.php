<?php
// Forms other than the monitor form let a value through that its column cannot
// store: text inputs without maxlength for a varchar(N) column, and number
// inputs without max for a tinyint/smallint column (user, zone, storage and
// control forms). Saving then failed with a database error. Each input now
// carries its column's limit. Password inputs are left alone: what is typed is
// hashed before it is stored, so the column size says nothing about it.
//
// Run as: php tests/php/test_form_limits.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$schema = file_get_contents($root.'/db/zm_create.sql.in');
$tables = array();
preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\n\)/s', $schema, $tm, PREG_SET_ORDER);
foreach ($tm as $t) {
  foreach (explode("\n", $t[2]) as $line) {
    if (preg_match('/^\s*`(\w+)`\s+(.+?),?\s*$/', $line, $c)) $tables[$t[1]][$c[1]] = $c[2];
  }
}
// Form field prefix => table it is saved to
$prefixes = array('user' => 'Users', 'newZone' => 'Zones', 'newStorage' => 'Storage',
  'newServer' => 'Servers', 'newGroup' => 'Groups', 'Control' => 'Controls', 'filter' => 'Filters');

$failures = 0;
$checked = 0;
$files = array_merge(glob($root.'/web/skins/classic/views/*.php'), glob($root.'/web/ajax/modals/*.php'));
foreach ($files as $file) {
  if (basename($file) == 'monitor.php' and strpos($file, '/views/') !== false) continue;
  foreach (explode("\n", file_get_contents($file)) as $n => $line) {
    if (!preg_match('/<input\b.*name="(\w+)\[(\w+)\]"/', $line, $im)) continue;
    if (!isset($prefixes[$im[1]])) continue;
    $def = $tables[$prefixes[$im[1]]][$im[2]] ?? null;
    if (!$def) continue;
    $type = preg_match('/type="(\w+)"/', $line, $t) ? $t[1] : 'text';
    $where = basename($file).':'.($n+1)." {$im[1]}[{$im[2]}]";
    if (in_array($type, array('text', 'url', 'email')) and preg_match('/^varchar\((\d+)\)/i', $def, $v)) {
      $checked++;
      if (!preg_match('/maxlength="(\d+)"/', $line, $ml) or $ml[1] > $v[1]) {
        $failures++;
        echo "FAIL - $where: varchar({$v[1]}) but maxlength ".($ml[1] ?? 'none')."\n";
      }
    } else if ($type == 'number' and preg_match('/^(tiny|small)int\b/i', $def, $i)) {
      $checked++;
      $unsigned = stripos($def, 'unsigned') !== false;
      $limit = array('tiny' => $unsigned ? 255 : 127, 'small' => $unsigned ? 65535 : 32767)[strtolower($i[1])];
      if (!preg_match('/\bmax="(\d+)"/', $line, $mx) or $mx[1] > $limit) {
        $failures++;
        echo "FAIL - $where: $def but max ".($mx[1] ?? 'none')."\n";
      }
    }
  }
}
echo "checked $checked inputs\n";
echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
