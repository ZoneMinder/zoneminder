<?php
// zmcControl() used to start, restart or reload zmc for a monitor that had
// already been deleted; only a 'stop' should reach zmdc. The method is taken
// from the source and run against stubs because Monitor needs a database.
// Run as: php tests/php/test_monitor_zmccontrol_deleted.php [root]   (root defaults to the repo)

error_reporting(E_ALL);

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

function extract_method($file, $name) {
  $source = file_get_contents($file);
  $start = strpos($source, "function $name(");
  if ($start === false) return null;
  $open = strpos($source, '{', $start);
  if ($open === false) return null;
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) return substr($source, $start, $i - $start + 1);
  }
  return null;
}

$file = $root.'/web/includes/Monitor.php';
$method = is_file($file) ? extract_method($file, 'zmcControl') : null;
if ($method === null) {
  $failures++;
  echo "FAIL - no zmcControl() in $file\n";
  echo "\n$failures failed\n";
  exit(1);
}

// The stubs the extracted method calls, in the namespace it expects.
if (!function_exists('ZM\Warning')) {
  eval('namespace ZM; function Warning($m) {} function Error($m) {}');
}
if (!function_exists('ZM\daemonControl')) {
  eval('namespace ZM; function daemonControl($command, $daemon, $args) {
    $GLOBALS[\'calls\'][] = "$command $daemon $args";
  }');
}

// No ZM_SERVER_ID is defined, so the local daemonControl branch runs.
eval('namespace ZM; class FakeMonitor {
  public $Id=5;
  public $Deleted=0;
  public $Device=\'/dev/video0\';
  public $ServerId=1;
  function Type() { return \'Ffmpeg\'; }
  function Capturing() { return \'Always\'; }
  function Deleted() { return $this->Deleted; }
  function ServerId() { return $this->ServerId; }
  '.$method.'
}');

function run_zmc($deleted, $mode, $has_mode = true) {
  $m = new ZM\FakeMonitor();
  $m->Deleted = $deleted;
  $GLOBALS['calls'] = array();
  if ($has_mode) $m->zmcControl($mode);
  else $m->zmcControl();
  $calls = $GLOBALS['calls'];
  unset($GLOBALS['calls']);
  return $calls;
}

# A live monitor restarts as before: stop, then start.
check(
  'live restart stops then starts zmc',
  run_zmc(0, 'restart'),
  array("stop zmc -m '5'", "start zmc -m '5'")
);

# A deleted monitor must not touch zmdc for start, restart, reload or no mode.
foreach (array('start', 'restart', 'reload') as $mode) {
  check("deleted $mode makes no call", run_zmc(1, $mode), array());
}
check('deleted no argument makes no call', run_zmc(1, false, false), array());

# A deleted monitor can still be stopped.
check(
  'deleted stop still calls zmdc',
  run_zmc(1, 'stop'),
  array("stop zmc -m '5'")
);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
