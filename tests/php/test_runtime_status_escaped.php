<?php
// runtimeStatus() in web/skins/classic/includes/functions.php puts the active run state name
// into the navbar as HTML; a state named like markup rendered as markup (stored HTML injection
// by anyone who can save states). It now escapes the name.

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

$src = @file_get_contents($root.'/web/skins/classic/includes/functions.php');
if ($src === false) {
  echo "FAIL - cannot read web/skins/classic/includes/functions.php (got missing file)\n";
  exit(1);
}

if (!preg_match('/^function runtimeStatus\(.*?^\}/ms', $src, $m)) {
  echo "FAIL - cannot extract runtimeStatus() (got no match)\n";
  exit(1);
}

$GLOBALS['state'] = null;

function translate($s) { return $s; }
function daemonCheck() { return $GLOBALS['state'] !== 'stopped'; }
function dbFetchOne($sql, $col = null, $args = array()) { return $GLOBALS['state']; }

eval($m[0]);

$failed = 0;

function test_check($label, $actual, $expected) {
  global $failed;
  if ($actual === $expected) {
    echo "ok - $label\n";
  } else {
    echo "FAIL - $label (got ".var_export($actual, true).")\n";
    $failed++;
  }
}

$GLOBALS['state'] = 'a<b>&c';
test_check('escapes markup in active state name', runtimeStatus(true), 'a&lt;b&gt;&amp;c');

$GLOBALS['state'] = 'Night';
test_check('returns plain active state name', runtimeStatus(true), 'Night');

$GLOBALS['state'] = 'default';
test_check('default active state displays Running', runtimeStatus(true), 'Running');

$GLOBALS['state'] = 'stopped';
test_check('non-running daemon displays Stopped', runtimeStatus(), 'Stopped');

exit($failed ? 1 : 0);
