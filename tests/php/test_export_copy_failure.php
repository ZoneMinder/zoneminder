<?php
/*
 * Detector for GitHub issue #5085: Event export ignores cp failures and
 * accumulates exec output across files.
 *
 * exportEvents() copies each event file with exec($cmd, $output, $return). Two
 * defects:
 *   1. $output is never reset between iterations, and PHP's exec() appends to a
 *      non-empty array, so the diagnostic (and any failure report) accumulates
 *      every file's output across the whole export.
 *   2. $return (the cp exit status) only reached a ZM\Debug line, so a failed
 *      copy passed silently and the export still handed back an archive that was
 *      missing files.
 *
 * This is a structural test: exportEvents() cannot be isolated (global-namespace
 * exec(), real filesystem, ZM\Event), so it reads the real source, extracts the
 * function body, and asserts the fix is present. Reverting the fix fails it.
 *
 * Run: php tests/php/test_export_copy_failure.php
 */

$src_file = dirname(__DIR__, 2).'/web/skins/classic/includes/export_functions.php';

$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label\n";
  }
}

$src = file_get_contents($src_file);
check('export_functions.php is readable', $src !== false);

// Extract the body of exportEvents() by brace matching from its signature.
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $brace = strpos($src, '{', $sig);
  if ($brace === false) return null;
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($src, $brace, $i - $brace + 1);
    }
  }
  return null;
}

$body = extract_function($src, 'exportEvents');
check('exportEvents() found', $body !== null);
$body = $body ?? '';

// The per-file copy call we are hardening.
check('exportEvents still copies each file with exec($cmd, $output, $return)',
  strpos($body, 'exec($cmd, $output, $return)') !== false);

// Defect 1: $output must be reset immediately before that exec() so output does
// not accumulate across files.
check('$output is reset to an empty array right before the per-file exec()',
  preg_match('/\$output\s*=\s*(array\(\)|\[\]);\s*(\/\/[^\n]*\n\s*)*exec\(\$cmd,\s*\$output,\s*\$return\)/', $body) === 1);

// Defect 2a: the copy return status is tested (not only logged at Debug).
check('the per-file copy return status is checked with if ($return ...)',
  preg_match('/exec\(\$cmd,\s*\$output,\s*\$return\);.*?if\s*\(\s*\$return\b/s', $body) === 1);

// Defect 2b: a failed copy is raised as an Error and recorded.
check('a failed copy logs ZM\\Error and sets a failure flag',
  preg_match('/if\s*\(\s*\$return[^)]*\)\s*\{[^}]*ZM\\\\Error\([^}]*\$copy_failed\s*=\s*true/s', $body) === 1);

// Defect 2c: the export actually fails (returns false) when a copy failed, so
// the caller reports it instead of handing back an incomplete archive.
check('exportEvents returns false when a copy failed',
  preg_match('/if\s*\(\s*\$copy_failed\s*\)\s*\{[^}]*return\s+false\s*;/s', $body) === 1);

echo "\n$tests checks, $failures failed\n";
exit($failures === 0 ? 0 : 1);
