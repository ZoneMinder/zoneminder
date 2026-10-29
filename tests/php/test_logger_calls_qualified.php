<?php
// Tests that every call to ZM's logger functions resolves.
//
// Debug(), Info(), Warning(), Error() and friends are defined in
// web/includes/logger.php, which is namespace ZM. A file that is also
// namespace ZM can call them unqualified, because php falls back to the
// enclosing namespace when it resolves a function name. A file in the global
// namespace cannot: the call has to say \ZM\Debug(...).
//
// Getting this wrong is silent. Nothing complains at include time, php -l is
// happy, and the file works perfectly until the one branch holding the call is
// taken -- at which point it is a fatal, and on an ajax endpoint that means a
// blank 500 and a save that reports nothing more useful than "an error
// occurred". That is how a bare Debug() in CronTab.php survived being written,
// reviewed and committed, and only showed itself when someone added a
// scheduled action.
//
// So every php file under web/ is tokenized and the unqualified calls are
// found. Using php's own tokenizer rather than a regex is the point: it knows
// what is a comment, what is a string, and what is a method call on an object
// that happens to have an Error() method.
//
// Run: php tests/php/test_logger_calls_qualified.php   (from the repo root)

$root = realpath(__DIR__.'/../..');

// The names logger.php exports, read from it rather than listed here so a new
// one is covered the day it is added.
$logger = file_get_contents($root.'/web/includes/logger.php');
preg_match_all('/^function\s+([A-Z][A-Za-z]*)\s*\(\s*\$[a-z]/m', $logger, $m);
$names = $m[1];

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

echo "the logger functions were found\n";
check('logger.php exports some', count($names) > 0, true);
// The ones that matter most, so a change to the regex above that quietly
// matched nothing would show up here rather than as a passing empty scan.
foreach (array('Debug', 'Info', 'Warning', 'Error') as $expected) {
  check($expected.' is among them', in_array($expected, $names, true), true);
}

/**
 * Is this file's code in namespace ZM?
 * @param array $tokens the file's tokens
 * @return bool
 */
function is_namespace_zm($tokens) {
  foreach ($tokens as $i => $token) {
    if (!is_array($token) or $token[0] !== T_NAMESPACE) continue;
    for ($j = $i + 1; $j < count($tokens); $j++) {
      $next = $tokens[$j];
      if (is_array($next) and $next[0] === T_WHITESPACE) continue;
      return (is_array($next) and $next[1] === 'ZM');
    }
  }
  return false;
}

/**
 * The unqualified logger calls in one file.
 * @param string $src the file's contents
 * @param array $names the logger function names
 * @return array one "line Name()" string per call
 */
function unqualified_calls($src, $names) {
  $tokens = @token_get_all($src);
  if (!$tokens) return array();
  // A file in namespace ZM may call them bare: php resolves a function name
  // against the current namespace before falling back to the global one.
  if (is_namespace_zm($tokens)) return array();

  $significant = array();
  foreach ($tokens as $token) {
    if (is_array($token) and
        in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) continue;
    $significant[] = $token;
  }

  $found = array();
  for ($i = 0; $i < count($significant); $i++) {
    $token = $significant[$i];
    if (!is_array($token) or $token[0] !== T_STRING) continue;
    if (!in_array($token[1], $names, true)) continue;
    // A call, not a mention.
    if (!isset($significant[$i + 1]) or $significant[$i + 1] !== '(') continue;
    // Not a method, a static call, a definition, or already qualified. A
    // qualified name arrives as T_NAME_QUALIFIED or T_NAME_FULLY_QUALIFIED on
    // php 8 and so never reaches here as a bare T_STRING, but the separator
    // check keeps this honest on php 7 too.
    $prev = isset($significant[$i - 1]) ? $significant[$i - 1] : null;
    if ($prev === '->' or $prev === '::') continue;
    if (is_array($prev) and in_array($prev[0], array(
        T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_NS_SEPARATOR), true)) continue;
    $found[] = $token[2].' '.$token[1].'()';
  }
  return $found;
}

echo "every logger call under web/ resolves\n";
$offenders = array();
$scanned = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/web'));
foreach ($iterator as $file) {
  if ($file->isDir() or $file->getExtension() !== 'php') continue;
  $path = $file->getPathname();
  // web/api is cakephp with its own logging, and assets are third party.
  if (strpos($path, '/api/') !== false) continue;
  if (strpos($path, '/assets/') !== false) continue;
  $scanned++;
  $calls = unqualified_calls(file_get_contents($path), $names);
  foreach ($calls as $call) {
    $offenders[] = substr($path, strlen($root) + 1).':'.$call;
  }
}
check('a useful number of files were scanned', $scanned > 100, true);
check('none of them calls a logger function unqualified', $offenders, array());

echo "and the scan can tell the difference\n";
// A scan that found nothing because it looks for nothing would pass the check
// above, so it is given one of each to classify.
check('a bare call in the global namespace is caught',
      unqualified_calls('<?php Debug("x");', $names), array('1 Debug()'));
check('the same call in namespace ZM is fine',
      unqualified_calls('<?php namespace ZM; Debug("x");', $names), array());
check('a qualified call is fine',
      unqualified_calls('<?php \ZM\Debug("x");', $names), array());
check('so is a relatively qualified one',
      unqualified_calls('<?php ZM\Debug("x");', $names), array());
check('a method named Error is not a logger call',
      unqualified_calls('<?php $o->Error("x");', $names), array());
check('nor is a static one',
      unqualified_calls('<?php Thing::Error("x");', $names), array());
check('nor is a definition',
      unqualified_calls('<?php function Error($s) {}', $names), array());
check('a mention without a call is not one either',
      unqualified_calls('<?php $a = Debug;', $names), array());
check('and neither a comment nor a string counts',
      unqualified_calls('<?php // Debug("x")'."\n".'$s = "Warning(\"x\")";', $names), array());

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
