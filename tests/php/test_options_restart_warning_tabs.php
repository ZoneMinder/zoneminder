<?php
// Saving the Images, MQTT or Telemetry options never showed "restart required", although
// zmc reads the image and MQTT settings and zmtelemetry its settings only when they start.
// The tab switch in actions/options.php had no case for them.
//
// The switch is taken from the source and run for each tab.
// Run as: php tests/php/test_options_restart_warning_tabs.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/includes/actions/options.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$start = strpos($source, "switch ( \$_REQUEST['tab'] )");
if ($start === false) { echo "FAIL - no tab switch in actions/options.php\n"; exit(1); }
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$switch = substr($source, $start, $i - $start + 1);

function restart_for($tab) {
  global $switch;
  $_REQUEST = array('tab' => $tab);
  $restartWarning = false;
  eval($switch);
  return !empty($restartWarning);
}

foreach (array('images', 'MQTT', 'telemetry', 'system', 'config', 'logging', 'network', 'mail', 'upload') as $tab) {
  check("saving the $tab tab asks for a restart", restart_for($tab), true);
}
foreach (array('web', 'API', 'highband', 'medband', 'lowband') as $tab) {
  check("saving the $tab tab does not ask for a restart", restart_for($tab), false);
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
