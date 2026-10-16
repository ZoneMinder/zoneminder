<?php
// Guards the invariant that including web/includes/database.php does not open a
// database connection. It used to call dbConnect() at file scope and, on
// failure, render views/no_database_connection.php and exit() - from inside a
// library include. Every model in web/includes requires this file, so loading a
// class opened a socket and could terminate the request.
//
// The check is structural (over the token stream) rather than behavioural,
// because database.php still cannot be included in isolation: it requires
// logger.php, which requires config.php, which reads ZoneMinder's configuration
// out of the database at include time. Breaking that cycle is a separate piece
// of work; until then this is what can be verified without an installed tree.
//
// Run: php tests/php/test_database_lazy_connect.php

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

// Anything at file scope (brace depth 0) that would run on include: control
// flow, and calls other than the include family and define(), which cannot
// themselves query.
//
// Note there is deliberately no "skip inside parentheses" rule. The call this
// exists to catch was `if ( !dbConnect() )`, i.e. nested inside an if
// condition; skipping parenthesised tokens made an earlier version of this
// check pass on the very code it is meant to reject.
function topLevelCalls($path) {
  $tokens = token_get_all(file_get_contents($path));
  $allowed = array('require', 'require_once', 'include', 'include_once', 'define', 'defined');
  $controlFlow = array(T_IF, T_SWITCH, T_WHILE, T_DO, T_FOR, T_FOREACH, T_TRY, T_ECHO, T_PRINT);
  $found = array();
  $depth = 0;
  foreach ($tokens as $i => $token) {
    if (is_string($token)) {
      if ($token === '{') $depth++;
      else if ($token === '}') $depth--;
      continue;
    }
    if ($depth !== 0) continue;
    if (in_array($token[0], $controlFlow, true)) {
      $found[] = $token[1];
      continue;
    }
    if ($token[0] !== T_STRING) continue;
    if (in_array(strtolower($token[1]), $allowed, true)) continue;

    // Skip the name in a declaration - `function foo(` looks like a call.
    $isDeclaration = false;
    for ($j = $i - 1; $j >= 0; $j--) {
      $prev = $tokens[$j];
      if (is_array($prev) and $prev[0] === T_WHITESPACE) continue;
      $isDeclaration = is_array($prev)
        and in_array($prev[0], array(T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT, T_CONST), true);
      break;
    }
    if ($isDeclaration) continue;

    for ($j = $i + 1; $j < count($tokens); $j++) {
      $next = $tokens[$j];
      if (is_array($next) and $next[0] === T_WHITESPACE) continue;
      if ($next === '(') $found[] = $token[1];
      break;
    }
  }
  return $found;
}

$dbPath = __DIR__.'/../../web/includes/database.php';
$source = file_get_contents($dbPath);

check('database.php calls nothing at include time', topLevelCalls($dbPath), array());

// Would pass vacuously if the accessors did not exist.
check('zmDbConn() is defined',
  (bool)preg_match('/^function zmDbConn\(\)/m', $source), true);
check('zmDbConnOrNull() is defined',
  (bool)preg_match('/^function zmDbConnOrNull\(\)/m', $source), true);

// Every query funnels through dbQuery(), so that is the one that must open the
// connection. If it went back to `global $dbConn` it would dereference `false`.
check('dbQuery() obtains the connection through the accessor',
  (bool)preg_match('/function dbQuery\([^)]*\)\s*\{\s*\$dbConn = zmDbConn\(\);/', $source), true);

// No caller should reach the raw global any more except the accessors,
// dbConnect() itself, and dbDisconnect().
preg_match_all('/function (\w+)\s*\([^)]*\)\s*\{(?:[^{}]|\{[^{}]*\})*?global \$dbConn/', $source, $m);
sort($m[1]);
check('only the connection plumbing touches the $dbConn global',
  $m[1], array('dbConnect', 'dbDisconnect', 'zmDbConn', 'zmDbConnOrNull'));

// Sanity-check the detector against files that do run code at include time, so
// a broken checker cannot silently report success above. The second fixture is
// the shape this file actually had before the change - the call nested inside
// an if condition - which an earlier version of this check failed to detect.
$fixture = tempnam(sys_get_temp_dir(), 'zmtest').'.php';

file_put_contents($fixture, "<?php\nrequire_once('x.php');\ndefine('A', 1);\nfunction f() { g(); }\ndbConnect();\n");
check('the detector finds a bare file-scope call', topLevelCalls($fixture), array('dbConnect'));

file_put_contents($fixture, "<?php\nfunction f() { g(); }\nif ( !dbConnect() ) {\n  include('v.php');\n  exit();\n}\n");
check('the detector finds a call nested in a file-scope if',
  topLevelCalls($fixture), array('if', 'dbConnect'));

unlink($fixture);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
