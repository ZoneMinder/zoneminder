<?php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
$file = $root.'/web/skins/classic/views/add_monitors.php';

if (!is_readable($file)) {
  fwrite(STDERR, "FAIL - cannot read $file\n");
  exit(1);
}
$src = file_get_contents($file);

$fails = 0;
function check($ok, $name) {
  global $fails;
  echo ($ok ? 'ok' : 'FAIL').' - '.$name."\n";
  if (!$ok) $fails++;
}

// Table opening tag
$tableTag = '';
if (preg_match('/<table\s+id="AddMonitorsTable".*?\n\s*>/s', $src, $m)) {
  $tableTag = $m[0];
}
check($tableTag !== '' && strpos($tableTag, 'data-escape="true"') !== false,
  'table has data-escape="true"');

// Collect th attribute text keyed by data-field
$fields = array();
$chunks = explode('<th ', $src);
foreach ($chunks as $chunk) {
  if (!preg_match('/^(.*?)(?<!\?)>/s', $chunk, $mm)) {
    continue;
  }
  $attrs = $mm[1];
  if (preg_match('/data-field="([^"]+)"/', $attrs, $fm)) {
    $fields[$fm[1]] = $attrs;
  }
}

check(isset($fields['Thumbnail']) && strpos($fields['Thumbnail'], 'data-escape="false"') !== false,
  'Thumbnail th has data-escape="false"');
check(isset($fields['buttons']) && strpos($fields['buttons'], 'data-escape="false"') !== false,
  'buttons th has data-escape="false"');
check(isset($fields['camera.Name']) && strpos($fields['camera.Name'], 'data-escape="false"') === false,
  'camera.Name th does not have data-escape="false"');

exit($fails ? 1 : 0);
