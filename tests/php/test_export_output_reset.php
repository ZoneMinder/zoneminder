<?php
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures; $tests++;
  if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, "function ".$name."(");
  if ($sig === false) return null;
  $brace = strpos($src, "{", $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === "{") $depth++;
    elseif ($src[$i] === "}") { $depth--; if ($depth === 0) return substr($src, $brace, $i - $brace + 1); }
  }
  return null;
}

/* The archive tar/zip exec() in exportEvents() appends to $output, which still
 * holds the last per-file cp output, so the "First line of output" error can
 * report a copy line. $output must be reset right before that exec. */
$src = file_get_contents(dirname(__DIR__, 2).'/web/skins/classic/includes/export_functions.php');
$body = extract_function($src, 'exportEvents');
check('exportEvents() found', $body !== null);
$body = $body ?? '';
check('$output reset before the archive exec()',
  preg_match('/\$output\s*=\s*(array\(\)|\[\]);\s*(\/\/[^\n]*\n\s*)*exec\(\$command,\s*\$output,\s*\$status\)/', $body) === 1);
check('$output reset before the per-file exec()',
  preg_match('/\$output\s*=\s*(array\(\)|\[\]);\s*(\/\/[^\n]*\n\s*)*exec\(\$cmd,\s*\$output,\s*\$return\)/', $body) === 1);
$dl = file_get_contents(dirname(__DIR__, 2).'/web/includes/download_functions.php');
check('concat exec in download_functions resets $output',
  preg_match('/\$output\s*=\s*array\(\);\s*exec\(\$cmd,\s*\$output,\s*\$return\)/', $dl) === 1);
echo "\n$tests checks, $failures failed\n";
exit($failures === 0 ? 0 : 1);

