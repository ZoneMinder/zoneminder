<?php
// Regression test for GHSA-xqfr-6v9x-cvwc: SQL injection through an all-digit
// filter field in the REST API's FilterComponent::buildFilter().
//
// The API router turns a named parameter written `0:<payload>` into an array
// element with the integer key 0 and the value <payload>. buildFilter() took
// the field name from that key, and `[a-z0-9.]+` happily matched "0", so it
// ran `$conditions["0"] = $value`. PHP casts a canonical-integer string key to
// an int, and CakePHP 2.x renders an integer-keyed condition as a raw,
// UNescaped SQL fragment (DboSource::conditionKeysToString -> _quoteFields),
// so the value went into the WHERE clause verbatim: UNION exfiltration, ACL
// bypass via a trailing comment, and a blind boolean oracle.
//
// The fix refuses an all-digit field name, so every key that reaches
// $conditions contains a letter or a dot and therefore stays a string key,
// which CakePHP quotes. The two call sites that pass $validFields were already
// safe; this closes the twelve that pass none, at the one chokepoint they all
// share.
//
// Cake's Component is stubbed, so this runs from a source checkout on master,
// release-1.38 and release-1.36 alike (release-1.36 throws a plain Exception,
// master/1.38 a BadRequestException; both extend Exception):
//   php tests/php/test_api_filter_numeric_field.php

class App {
  public static function uses($class, $package) {}
}
class Component {}
class BadRequestException extends Exception {}

require_once __DIR__.'/../../web/api/app/Controller/Component/FilterComponent.php';

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

// Run buildFilter() the way the unguarded controllers do: no $validFields.
// Returns the rejection message, or an array describing the built condition.
function attempt($named) {
  try {
    $conditions = (new FilterComponent())->buildFilter($named);
  } catch (Exception $e) {
    return array('rejected' => $e->getMessage());
  }
  return array('built' => $conditions);
}

// The security invariant: no top-level condition key is an integer, because an
// integer key is the raw-SQL-fragment path.
function hasIntegerTopKey($conditions) {
  foreach ($conditions as $k => $v) {
    if (is_int($k)) return true;
  }
  return false;
}

// --- the attack is refused -------------------------------------------------

// `0:1=1` : key 0, value "1=1" (an always-true fragment that, with a trailing
// comment, drops the ACL conjunct the controller appends).
check('all-digit field 0 is rejected',
  attempt(array('1=1')), array('rejected' => 'Invalid filter field: 0'));

// `0:1=1 UNION SELECT ... FROM Users-- ` : the credential-exfil payload.
check('UNION payload on an all-digit field is rejected',
  attempt(array('1=1 UNION SELECT Username, Password FROM Users-- ')),
  array('rejected' => 'Invalid filter field: 0'));

// The same shape written as an explicit integer key.
check('explicit integer key 0 is rejected',
  attempt(array(0 => 'anything')), array('rejected' => 'Invalid filter field: 0'));

check('non-zero integer key is rejected',
  attempt(array(7 => 'x')), array('rejected' => 'Invalid filter field: 7'));

check('multi-digit integer key is rejected',
  attempt(array(42 => 'x')), array('rejected' => 'Invalid filter field: 42'));

// --- legitimate filtering still works, and never on an integer key ---------

$r = attempt(array('Id' => '5'));
check('a normal field builds a condition', isset($r['built']), true);
check('a normal field keeps a string key', isset($r['built']) ? !hasIntegerTopKey($r['built']) : null, true);
check('a normal field keeps its value', isset($r['built']) ? in_array('5', $r['built'], true) : null, true);

$r = attempt(array('Score >' => '5'));
check('a field with an operator builds a condition', isset($r['built']), true);
check('a field with an operator keeps a string key', isset($r['built']) ? !hasIntegerTopKey($r['built']) : null, true);

// A numeric VALUE on a real field is fine; only a numeric FIELD is refused.
$r = attempt(array('Name' => '0'));
check('a numeric value on a real field is allowed', isset($r['built']), true);
check('a numeric value on a real field keeps a string key', isset($r['built']) ? !hasIntegerTopKey($r['built']) : null, true);
check('a numeric value on a real field keeps its value', isset($r['built']) ? in_array('0', $r['built'], true) : null, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
