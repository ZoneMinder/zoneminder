<?php
// Guards the invariant that including web/includes/auth.php only *defines*
// things. It used to run a 130-line block at file scope, so requiring the file
// authenticated the current request: reading $_REQUEST, opening a session,
// querying the database, potentially logging a user in and rewriting their
// stored password hash. That work now lives in zm_authenticate_request(), which
// callers invoke deliberately.
//
// This is a structural check over the token stream rather than a behavioural
// one, because actually including auth.php still requires a database (User.php
// pulls in database.php, which connects at include time). If that changes, this
// can become a real behavioural test.
//
// Run: php tests/php/test_auth_no_include_side_effects.php

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

// Return every statement at file scope (brace depth 0) that would *execute*
// when the file is included: control flow, and calls other than the include
// family and define(). Each entry is "line: snippet".
function topLevelExecutableStatements($path) {
  $tokens = token_get_all(file_get_contents($path));
  $allowedCalls = array('require', 'require_once', 'include', 'include_once', 'define', 'defined');
  $controlFlow = array(T_IF, T_SWITCH, T_WHILE, T_DO, T_FOR, T_FOREACH, T_TRY, T_ECHO, T_PRINT);

  $found = array();
  $depth = 0;
  $parens = 0;
  foreach ($tokens as $i => $token) {
    if (is_string($token)) {
      if ($token === '{') $depth++;
      else if ($token === '}') $depth--;
      else if ($token === '(') $parens++;
      else if ($token === ')') $parens--;
      continue;
    }
    if ($depth !== 0 or $parens !== 0) continue;

    list($id, $text, $line) = array($token[0], $token[1], $token[2]);

    if (in_array($id, $controlFlow, true)) {
      $found[] = "$line: $text";
      continue;
    }
    // A bare function call at file scope, e.g. `zm_authenticate_request();`.
    // The name in a `function foo(` / `class Foo` declaration is also a T_STRING
    // followed by '(', so look back and skip declarations.
    if ($id === T_STRING and !in_array(strtolower($text), $allowedCalls, true)) {
      $declaration = false;
      for ($j = $i - 1; $j >= 0; $j--) {
        $prev = $tokens[$j];
        if (is_array($prev) and $prev[0] === T_WHITESPACE) continue;
        $declaration = is_array($prev)
          and in_array($prev[0], array(T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT, T_CONST), true);
        break;
      }
      if ($declaration) continue;
      for ($j = $i + 1; $j < count($tokens); $j++) {
        $next = $tokens[$j];
        if (is_array($next) and $next[0] === T_WHITESPACE) continue;
        if ($next === '(') $found[] = "$line: $text(";
        break;
      }
    }
  }
  return $found;
}

$authPath = __DIR__.'/../../web/includes/auth.php';

check('auth.php executes nothing at include time',
  topLevelExecutableStatements($authPath), array());

// The extracted work must still exist, or the above would pass vacuously.
$source = file_get_contents($authPath);
check('zm_authenticate_request() is defined',
  (bool)preg_match('/^function zm_authenticate_request\(\)/m', $source), true);

// Sanity-check the detector itself against a file that does run code on
// include, so a broken checker cannot silently report success above.
$fixture = tempnam(sys_get_temp_dir(), 'zmtest').'.php';
file_put_contents($fixture, "<?php\nrequire_once('x.php');\nfunction f() { if (true) { g(); } }\nif (SOMETHING) {\n  h();\n}\n");
$detected = topLevelExecutableStatements($fixture);
unlink($fixture);
check('the detector finds a file-scope if()', count($detected), 1);
check('the detector ignores code inside functions and require_once',
  $detected, array('4: if'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
