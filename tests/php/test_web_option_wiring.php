<?php
// ZM_WEB_*_SHOW_PROGRESS was configurable, translated into several languages and
// shown in Options, but nothing ever read it, so the setting did nothing at all
// (issue #4850). These checks pin the two halves of that: every per-bandwidth
// web option the classic skin defines has to be consumed somewhere, and the
// progress display specifically has to stay gated on ZM_WEB_SHOW_PROGRESS.
// Run: php tests/php/test_web_option_wiring.php   (from the repo root)

$root = __DIR__.'/../..';
$skin_config = $root.'/web/skins/classic/includes/config.php';

$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

// Every file the skin could read an option from.
function skin_sources($root, $exclude) {
  $found = array();
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/web'));
  foreach ($it as $file) {
    $path = $file->getPathname();
    if (!preg_match('/\.(php|js)$/', $path)) continue;
    if (realpath($path) === realpath($exclude)) continue;
    if (strpos($path, '/node_modules/') !== false) continue;
    $found[] = $path;
  }
  return $found;
}

$config_src = file_get_contents($skin_config);
preg_match('/\$bandwidth_settings\s*=\s*array\((.*?)\);/s', $config_src, $required);
preg_match('/\$bandwidth_optional_settings\s*=\s*array\((.*?)\);/s', $config_src, $optional);
$names = array();
if (!empty($required)) {
  preg_match_all("/'([A-Z_]+)'/", $required[1], $m);
  $names = array_merge($names, $m[1]);
}
if (!empty($optional)) {
  preg_match_all("/'([A-Z_]+)'\s*=>/", $optional[1], $m);
  $names = array_merge($names, $m[1]);
}
$options = array_values(array_unique(array_map(function($name) { return 'ZM_WEB_'.$name; }, $names)));
check('the skin defines per-bandwidth web options', count($options) > 0);

$sources = skin_sources($root, $skin_config);
$haystack = '';
foreach ($sources as $path) $haystack .= file_get_contents($path)."\n";

$dead = array();
foreach ($options as $opt) {
  if (strpos($haystack, $opt) === false) $dead[] = $opt;
}
check('every per-bandwidth web option is read somewhere', count($dead) === 0,
  'defined but never used: '.implode(', ', $dead));

// The specific wiring #4850 was about. The bar itself must stay, since the
// option's help text keeps the navigation and only drops the progress display.
$event_src = file_get_contents($root.'/web/skins/classic/views/event.php');

check('the replay position fill is gated on ZM_WEB_SHOW_PROGRESS',
  (bool)preg_match('/id="progressBox".*ZM_WEB_SHOW_PROGRESS/s', substr($event_src,
    strpos($event_src, 'id="progressBox"') - 400, 800)));

check('the progress readout is gated on ZM_WEB_SHOW_PROGRESS',
  (bool)preg_match('/<span id="progress"[^>]*ZM_WEB_SHOW_PROGRESS/', $event_src));

check('the progress bar itself is not gated, so jumping still works',
  (bool)preg_match('/<div id="progressBar" style="width: 100%;">/', $event_src),
  'the help text keeps the navigation aspect when the progress display is off');

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
