<?php
/*
 * array_recursive_diff() and ZM_Object::changes() must compare values strictly.
 *
 * The fix extracts scalar_values_differ($old, $new) in web/includes/functions.php:
 * scalars compare as strings (null === ''), booleans as ints, arrays fall back to
 * loose !=. array_recursive_diff() and changes() use it instead of loose `!=`.
 *
 * This test extracts those functions from the source tree and evals them, so it
 * also runs against the original code (where scalar_values_differ is absent) and
 * fails on behaviour rather than fataling.
 *
 * argv[1] is the REPO root; it defaults to dirname(__DIR__, 2).
 *
 * Run: php tests/php/test_changes_strict_compare.php [repo-root]
 */
$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $start = strrpos(substr($src, 0, $sig), "\n") + 1;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') { $depth--; if ($depth === 0) return substr($src, $start, $i - $start + 1); }
  }
  return null;
}

/* array_recursive_diff() calls ZM\Warning on bad input; stub it. */
eval('namespace ZM { function Warning($m) {} }');

/* changes() may call the global (in its namespace) Warning; stub it too. */
if (!function_exists('Warning')) {
  function Warning($m) {}
}

/* ---- Part (a): array_recursive_diff / scalar_values_differ ---- */
$fnSrc = file_get_contents($root.'/web/includes/functions.php');
$ard = extract_function($fnSrc, 'array_recursive_diff');
check('array_recursive_diff() found in functions.php', $ard !== null);
if ($ard !== null) eval($ard);

$svd = extract_function($fnSrc, 'scalar_values_differ');
/* Only eval scalar_values_differ when it exists (original code lacks it). */
if ($svd !== null) eval($svd);

if ($ard !== null) {
  check("['a'=>0] vs ['a'=>''] differs", count(array_recursive_diff(['a'=>0], ['a'=>''])) > 0);
  check("['a'=>null] vs ['a'=>0] differs", count(array_recursive_diff(['a'=>null], ['a'=>0])) > 0);
  check("['a'=>'10'] vs ['a'=>'1e1'] differs", count(array_recursive_diff(['a'=>'10'], ['a'=>'1e1'])) > 0);
  check("['a'=>'1e3'] vs ['a'=>'1000'] differs", count(array_recursive_diff(['a'=>'1e3'], ['a'=>'1000'])) > 0);
  check("['a'=>5] vs ['a'=>'5'] same", count(array_recursive_diff(['a'=>5], ['a'=>'5'])) === 0);
  check("['a'=>null] vs ['a'=>''] same", count(array_recursive_diff(['a'=>null], ['a'=>''])) === 0);
  check("['a'=>false] vs ['a'=>'0'] same", count(array_recursive_diff(['a'=>false], ['a'=>'0'])) === 0);
  check("['a'=>true] vs ['a'=>1] same", count(array_recursive_diff(['a'=>true], ['a'=>1])) === 0);
}

/* ---- Part (b): ZM_Object::changes() ---- */
$objSrc = file_get_contents($root.'/web/includes/Object.php');
$changes = extract_function($objSrc, 'changes');
check('changes() found in Object.php', $changes !== null);

if ($changes !== null) {
  $classCode = 'class ChangesProbe {'
    . ' protected $defaults = array();'
    . ' public $Foo = 0;'
    . ' public $bar = null;'
    . ' public function Bar() { return $this->bar; }'
    . ' public function isSetter($f) { return true; }'
    . ' ' . $changes
    . ' }';
  $ok = eval($classCode);
  check('changes() evals into a probe class', $ok !== false);

  if ($ok !== false) {
    $probe = new ChangesProbe();

    $probe->Foo = 0;
    check("changes(['Foo'=>'']) on Foo=0 reports a change",
          count($probe->changes(['Foo'=>''])) > 0);

    $probe->Foo = 0;
    check("changes(['Foo'=>'0']) on Foo=0 reports no change",
          count($probe->changes(['Foo'=>'0'])) === 0);

    $probe->bar = null;
    check("changes(['Bar'=>'']) on Bar()===null reports no change",
          count($probe->changes(['Bar'=>''])) === 0);

    $probe->bar = 0;
    check("changes(['Bar'=>'']) on Bar()===0 reports a change",
          count($probe->changes(['Bar'=>''])) > 0);

    $probe->bar = '10';
    check("changes(['Bar'=>'1e1']) on Bar()==='10' reports a change",
          count($probe->changes(['Bar'=>'1e1'])) > 0);

    $probe->Foo = '10';
    check("changes(['Foo'=>'1e1']) on Foo='10' reports a change",
          count($probe->changes(['Foo'=>'1e1'])) > 0);
  }
}

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
