<?php
// zm_setcookie() takes an options array as its third argument. Given a bare
// expiry time it sets 'path' on an int, which is an Error on PHP 8, so the
// page dies instead of clearing the cookie.
//
// php tests/php/test_setcookie_options.php   (from the repo root)

$root = realpath(__DIR__.'/../..');
require_once $root.'/web/includes/session.php';

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function call_setcookie($options) {
  try {
    @zm_setcookie('zmTest', '', $options);
  } catch (\Error $e) {
    return get_class($e);
  }
  return 'set';
}

check('an options array is accepted', call_setcookie(array('expires' => time() - 86400)), 'set');
check('a bare expiry time is an Error', call_setcookie(time() - 86400), 'Error');

// The third argument of each call in the web ui, as its first token.
function third_arguments($src) {
  $tokens = array_values(array_filter(token_get_all($src), function($t) {
    return !(is_array($t) and in_array($t[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true));
  }));
  $found = array();
  for ($i = 0; $i < count($tokens) - 1; $i++) {
    if (!is_array($tokens[$i]) or $tokens[$i][1] !== 'zm_setcookie' or $tokens[$i + 1] !== '(') continue;
    if (isset($tokens[$i - 1]) and is_array($tokens[$i - 1]) and $tokens[$i - 1][0] === T_FUNCTION) continue;
    $depth = 0;
    $arg = 0;
    for ($j = $i + 1; $j < count($tokens); $j++) {
      $t = $tokens[$j];
      if ($t === '(' or $t === '[') { $depth++; if ($depth == 1) continue; }
      if ($t === ')' or $t === ']') { $depth--; if ($depth == 0) break; }
      if ($depth == 1 and $t === ',') {
        $arg++;
        if ($arg == 2) {
          $next = $tokens[$j + 1];
          $found[] = array('line' => $tokens[$i][2], 'first' => is_array($next) ? $next[1] : $next);
          break;
        }
      }
    }
  }
  return $found;
}

$bad = array();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/web'));
foreach ($iterator as $file) {
  if ($file->isDir() or $file->getExtension() !== 'php') continue;
  $path = $file->getPathname();
  if (strpos($path, '/api/lib/') !== false or strpos($path, '/assets/') !== false) continue;
  foreach (third_arguments(file_get_contents($path)) as $find) {
    if ($find['first'] === 'array' or $find['first'] === '[' or $find['first'][0] === '$') continue;
    $bad[] = substr($path, strlen($root) + 1).':'.$find['line'];
  }
}
check('every zm_setcookie call passes an options array', $bad, array());

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
