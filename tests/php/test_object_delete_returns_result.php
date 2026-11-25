<?php
/*
 * The report and filter delete actions check the result of $object->delete().
 * Report and Filter do not override delete(), so that result comes from
 * ZM_Object::delete(), which returns true when the DELETE ran and false when
 * dbQuery() reported a failure (null). This runs the real method body.
 *
 * Run: php tests/php/test_object_delete_returns_result.php [repo-root]
 */
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) { echo "  ok   - $label\n"; } else { $failures++; echo "  FAIL - $label\n"; }
}
function extract_method($src, $name) {
  $sig = strpos($src, 'public function '.$name.'(');
  if ($sig === false) return null;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') { $depth--; if ($depth === 0) return substr($src, $sig, $i - $sig + 1); }
  }
  return null;
}

$GLOBALS['dbResult'] = true;
$GLOBALS['dbCalls'] = array();
function dbQuery($sql, $params = array()) {
  $GLOBALS['dbCalls'][] = array($sql, $params);
  return $GLOBALS['dbResult'];
}

foreach (array('Object.php', 'Report.php', 'Filter.php') as $f) {
  $src = file_get_contents($root.'/web/includes/'.$f);
  if ($f != 'Object.php') {
    check("$f does not override delete()", strpos($src, 'function delete(') === false);
  }
}

$method = extract_method(file_get_contents($root.'/web/includes/Object.php'), 'delete');
check('ZM_Object has a delete() method', $method !== null);
eval('class DeleteProbe { public static $table = "Reports"; public $Id = 5; '.$method.' }');

$GLOBALS['dbResult'] = true;
$r = (new DeleteProbe())->delete();
check('a DELETE that ran returns true', $r === true);
check('it deleted the row by Id', $GLOBALS['dbCalls'][0][1] === array(5));

$GLOBALS['dbResult'] = null;
$r = (new DeleteProbe())->delete();
check('a DELETE that failed (dbQuery null) returns false', $r === false);

echo "$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
