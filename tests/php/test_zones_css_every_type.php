<?php
// The zone overlay gives each polygon the class of its zone Type. zones.css had
// colours for Active, Inclusive, Exclusive and Preclusive only, so Inactive and
// Privacy zones fell back to the SVG default (a faint black fill, no outline).
//
// Run as: php tests/php/test_zones_css_every_type.php [repo root]

$root = $argv[1] ?? __DIR__.'/../..';
$schema = file_get_contents($root.'/db/zm_create.sql.in');
if (!preg_match("/`Type` enum\\(([^)]*'Privacy'[^)]*)\\)/", $schema, $m)) {
  echo "FAIL - could not read the Zones Type enum\n";
  exit(1);
}
$types = array_map(function($t) { return trim($t, "' "); }, explode(',', $m[1]));

$failures = 0;
foreach (array('web/skins/classic/css/base/views/zones.css', 'web/skins/classic/css/base/zones.css') as $file) {
  $css = file_get_contents($root.'/'.$file);
  foreach ($types as $type) {
    $ok = (bool)preg_match('/\.'.preg_quote($type, '/').'\s*\{[^}]*\bstroke\s*:/', $css);
    echo ($ok ? 'ok' : 'FAIL')." - $file colours $type zones\n";
    if (!$ok) $failures++;
  }
}
echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
