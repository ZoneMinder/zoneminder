<?php
// Regression test for the export-archive path-traversal hardening on
// release-1.36 (GHSA-fvx4-86xp-9v22).
//
// views/archive.php sanitized the user-controlled `file` parameter with
// str_replace('/','') only, so `..` survived. On 1.36 this view is
// self-limiting (no separator survives, so `file=..` only names a directory,
// which readfile() cannot send, and there is no download.php here), but the
// weak pattern is hardened for parity with master/release-1.38: the name must
// be a single path component, and the resolved path must stay at or below
// ZM_DIR_EXPORTS.
//
// archive.php cannot be included standalone (it runs the web bootstrap), so
// this test reads its source and checks the guards are in place, then exercises
// the same predicates behaviourally.
//
// Runs from a source checkout: php tests/php/test_archive_path_traversal.php

$failures = 0;
$passes = 0;
function check($name, $cond) {
  global $failures, $passes;
  if ($cond) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name\n"; }
}

// archive.php is at web/views/ on some branches and skins/classic/views/ on others.
$src = @file_get_contents(__DIR__.'/../../web/views/archive.php');
if ($src === false) $src = @file_get_contents(__DIR__.'/../../web/skins/classic/views/archive.php');
if ($src === false) { echo "not ok - read archive.php\n"; exit(1); }

// --- structural: the guards are present -------------------------------------

check('rejects a lone .. filename',
  strpos($src, "\$filename === '..'") !== false);
check('rejects a separator or null byte in the filename',
  preg_match('/preg_match\(.*\$filename\)\s*or\s*strpos\(\$filename, "\\\\0"\)/', $src) === 1);
check('falls back to the generated default name',
  preg_match('/\$filename = "zmExport_\$connkey\.\$file_ext";/', $src) === 1);
check('resolves the final path with realpath for containment',
  strpos($src, 'realpath($filename_path)') !== false
  and strpos($src, 'realpath(ZM_DIR_EXPORTS)') !== false);
check('the readfile gate requires the containment check',
  preg_match('/is_readable\(\$filename_path\)\s*and\s*\$within_exports/', $src) === 1);
check('no longer relies on str_replace to sanitize the filename',
  strpos($src, "str_replace('/', '', \$filename)") === false);

// --- behavioural: the guard predicate rejects traversal, keeps real names ---

// Mirror the predicate from archive.php.
function archive_name_is_rejected($filename) {
  return ($filename === '' or $filename === '.' or $filename === '..'
    or preg_match('@[/\\\\]@', $filename) or strpos($filename, "\0") !== false);
}
check('a plain archive name is accepted', !archive_name_is_rejected('zmExport_123456.zip'));
check('a tar.gz name is accepted', !archive_name_is_rejected('zmExport_1.tar.gz'));
check('a lone .. is rejected', archive_name_is_rejected('..'));
check('a slash path is rejected', archive_name_is_rejected('../secret.txt'));
check('a backslash path is rejected', archive_name_is_rejected('..\\secret'));
check('a null byte is rejected', archive_name_is_rejected("x\0.zip"));

// Mirror the containment check against a temp directory.
function archive_within_exports($path, $base) {
  $real_base = realpath($base);
  $real_path = realpath($path);
  return ($real_base !== false and $real_path !== false
    and ($real_path === $real_base or strpos($real_path, $real_base.DIRECTORY_SEPARATOR) === 0));
}
$base = sys_get_temp_dir().'/zm_fvx4arch_'.getmypid().'_'.mt_rand();
@mkdir($base, 0700, true);
file_put_contents($base.'/zmExport.zip', 'x');
$outside = sys_get_temp_dir().'/zm_fvx4arch_out_'.getmypid().'_'.mt_rand().'.txt';
file_put_contents($outside, 'x');
check('a file inside exports passes containment',
  archive_within_exports($base.'/zmExport.zip', $base));
check('a ..-escaping path fails containment',
  !archive_within_exports($base.'/../'.basename($outside), $base));
@unlink($base.'/zmExport.zip');
@rmdir($base);
@unlink($outside);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
