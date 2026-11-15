<?php
/*
 * ZM_Object::_find() must bind values in the same order as its placeholders.
 * An IN (array) parameter used to be merged with `$values += $value`, a
 * numeric-key array union that dropped values when an earlier parameter was
 * already bound.
 *
 * Run: php tests/php/test_object_find_in_values.php
 */
$root = dirname(__DIR__, 2);
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

$captured = [];
function dbFetchAll($sql, $col = null, $params = []) {
  global $captured;
  $captured = [$sql, $params];
  return [];
}
function Error($m) {}
class Stub { public static $table = 'T'; public function __construct($r = null) {} }

$fn = extract_function(file_get_contents($root.'/web/includes/Object.php'), '_find');
check('_find() found', $fn !== null);
eval('class FindUnderTest { '.$fn.' }');

function run($params, $options = null) {
  global $captured;
  FindUnderTest::_find('Stub', $params, $options);
  return $captured;
}

list($sql, $v) = run(['ServerId'=>1, 'Id'=>[5,6]]);
check('scalar then IN binds [1,5,6]', $v === [1,5,6]);
check('placeholder count matches', substr_count($sql, '?') === count($v));

list($sql, $v) = run(['Id'=>[5,6], 'ServerId'=>1]);
check('IN then scalar binds [5,6,1]', $v === [5,6,1]);

list($sql, $v) = run(['A'=>[1,2], 'B'=>[3,4,5]]);
check('two IN arrays bind [1,2,3,4,5]', $v === [1,2,3,4,5]);

list($sql, $v) = run(['A'=>2, 'B'=>['x'=>7,'y'=>8], 'C'=>3]);
check('associative IN array is re-indexed', $v === [2,7,8,3]);

list($sql, $v) = run(['A'=>1, 'B'=>[], 'C'=>2]);
check('empty IN array adds no values', $v === [1,2] && substr_count($sql, '?') === 2);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
