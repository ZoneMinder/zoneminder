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

/* Event::delete() must not remove the secondary storage copy when that
 * storage has DoDelete off, as it already does for the primary storage. */
$src = file_get_contents(dirname(__DIR__, 2).'/web/includes/Event.php');
$body = extract_function($src, 'delete');
check('delete() found', $body !== null);
$body = $body ?? '';
check('Deep branch checks secondary DoDelete before deletePath',
  preg_match('/\$SecondaryStorage\s*=\s*\$this->SecondaryStorage\(\);\s*if\s*\(.*?!\s*\$SecondaryStorage->DoDelete\(\)/s', $body) === 1);
check('non-Deep branch checks secondary DoDelete before deletePath',
  preg_match('/\$Storage\s*=\s*\$this->SecondaryStorage\(\);\s*if\s*\(.*?!\s*\$Storage->DoDelete\(\)/s', $body) === 1);
echo "\n$tests checks, $failures failed\n";
exit($failures === 0 ? 0 : 1);

