<?php
// Tests the path-traversal guards for export downloads (GHSA-fvx4-86xp-9v22).
//
// views/download.php and views/archive.php sanitized the user-controlled `file`
// and `export_root` parameters with str_replace('/','') only, so `..` survived
// and export_root=.. escaped the downloads directory into an arbitrary-file
// read (readfile) and delete (unlink). safeExportComponent() rejects anything
// that is not a single, safe path component; pathWithinExportDir() is the
// realpath() backstop in front of readfile()/unlink().
//
// web/includes/download_functions.php only defines functions at include time,
// so this runs from a source checkout:
//   php tests/php/test_download_path_traversal.php

require_once __DIR__.'/../../web/includes/download_functions.php';

$failures = 0;
$passes = 0;
function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

// --- safeExportComponent() ---------------------------------------------------

// Legitimate export names pass through unchanged.
check('a plain archive name is kept', safeExportComponent('zmExport_123456.zip'), 'zmExport_123456.zip');
check('a tar.gz name is kept', safeExportComponent('zmExport_123456.tar.gz'), 'zmExport_123456.tar.gz');
check('an export_root is kept', safeExportComponent('zmExport_123456'), 'zmExport_123456');
check('a merged mp4 name with spaces and colons is kept',
  safeExportComponent('Front Door 2026-01-01 12:00:00 to 2026-01-01 12:05:00.mp4'),
  'Front Door 2026-01-01 12:00:00 to 2026-01-01 12:05:00.mp4');

// Traversal and path attempts are rejected (return '').
check('a lone .. is rejected', safeExportComponent('..'), '');
check('a lone . is rejected', safeExportComponent('.'), '');
check('the empty string is rejected', safeExportComponent(''), '');
check('a forward-slash path is rejected', safeExportComponent('../secret.txt'), '');
check('a nested path is rejected', safeExportComponent('a/b'), '');
check('a backslash path is rejected', safeExportComponent('..\\secret'), '');
check('a leading-slash absolute path is rejected', safeExportComponent('/etc/passwd'), '');
check('a null byte is rejected', safeExportComponent("secret.txt\0.zip"), '');
// A name that merely contains dots but is not a traversal is still a valid name.
check('a dotted file name is kept', safeExportComponent('my..file.zip'), 'my..file.zip');

// --- pathWithinExportDir() ---------------------------------------------------

$base = sys_get_temp_dir().'/zm_fvx4_'.getmypid().'_'.mt_rand();
@mkdir($base.'/sub', 0700, true);
file_put_contents($base.'/sub/inside.txt', 'x');
$outside = sys_get_temp_dir().'/zm_fvx4_outside_'.getmypid().'_'.mt_rand().'.txt';
file_put_contents($outside, 'x');

check('a file inside the base resolves',
  pathWithinExportDir($base.'/sub/inside.txt', $base), realpath($base.'/sub/inside.txt'));
check('a ..-escaping path to a real outside file is refused',
  pathWithinExportDir($base.'/sub/../../'.basename($outside), $base), false);
check('an absolute path outside the base is refused',
  pathWithinExportDir($outside, $base), false);
check('the base directory itself resolves',
  pathWithinExportDir($base, $base), realpath($base));
check('a non-existent path is refused',
  pathWithinExportDir($base.'/nope.txt', $base), false);

// A symlink inside the base that points outside is refused.
$link = $base.'/evil.txt';
if (@symlink($outside, $link)) {
  check('a symlink out of the base is refused', pathWithinExportDir($link, $base), false);
  @unlink($link);
} else {
  echo "ok - a symlink out of the base is refused (skipped, symlink unavailable)\n";
  $passes++;
}

@unlink($base.'/sub/inside.txt');
@rmdir($base.'/sub');
@rmdir($base);
@unlink($outside);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
