<?php
// Exporting events created the per-event subdirectory with a bare mkdir(), so
// its mode came from the PHP process umask. A strict umask leaves it with no
// execute bit, and a directory with no execute bit cannot be traversed even by
// root, because the search check is the one thing CAP_DAC_OVERRIDE does not
// bypass. Every copy into it then failed with permission denied and the export
// came out incomplete with nothing useful in the UI. Issue #5074, reported with
// this diagnosis by techrockedge.
// Run: php tests/php/test_export_dir_permissions.php   (from the repo root)

$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

$root = __DIR__.'/../..';
$base = sys_get_temp_dir().'/zm_export_perm_'.getmypid();
@mkdir($base);

// The reporter's environment: a umask that strips the execute bits.
$old_umask = umask(0117);

// What the code used to do.
$bare = $base.'/bare';
@mkdir($bare);
$bare_mode = fileperms($bare) & 0777;
check('a bare mkdir under a strict umask loses the execute bits',
  ($bare_mode & 0111) === 0,
  sprintf('got mode %04o, so this platform does not reproduce the report', $bare_mode));

// Writing through such a directory is what actually broke.
$probe = @file_put_contents($bare.'/probe.txt', 'x');
check('and the directory cannot be written through', $probe === false,
  'expected the write to fail, so the export failure is reproduced');

// What the code does now.
$fixed = $base.'/fixed';
@mkdir($fixed);
chmod($fixed, 0700);
$fixed_mode = fileperms($fixed) & 0777;
check('an explicit chmod gives a predictable mode regardless of umask',
  $fixed_mode === 0700, sprintf('got mode %04o, want 0700', $fixed_mode));
check('and the directory can be written through',
  @file_put_contents($fixed.'/probe.txt', 'x') !== false);

umask($old_umask);
@unlink($fixed.'/probe.txt');
@chmod($bare, 0700);
@rmdir($bare);
@rmdir($fixed);
@rmdir($base);

// Guard the source itself: every mkdir in the export path must be followed by an
// explicit chmod, since this was the one of three that was not.
$src = file_get_contents($root.'/web/skins/classic/includes/export_functions.php');
$lines = explode("\n", $src);
$unchmodded = array();
foreach ($lines as $i => $line) {
  if (strpos($line, 'mkdir(') === false) continue;
  // chmod follows within a few lines, past any error handling and comments.
  $window = implode("\n", array_slice($lines, $i, 12));
  if (strpos($window, 'chmod(') === false) $unchmodded[] = $i + 1;
}
check('every mkdir in the export path sets its mode explicitly',
  count($unchmodded) === 0,
  'mkdir with no following chmod at line(s): '.implode(', ', $unchmodded));

// And a failed mkdir must skip the event rather than writing into a path that
// does not exist, which is what made the failure silent.
check('a failed export mkdir skips that event',
  (bool)preg_match('/Can\'t mkdir \$event_dir"\);\s*\n\s*continue;/', $src));

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
