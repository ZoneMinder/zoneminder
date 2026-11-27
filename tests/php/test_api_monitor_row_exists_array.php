<?php
// Monitors.ServerId and Monitors.StorageId are checked with Monitor::rowExists(). A nested array value,
// such as {"a":{"b":1}}, went into the lookup as the Id condition; Cake put the word Array in the SQL and
// MySQL answered with an unknown column, so the client got a 500 instead of the "must be a single value"
// validation error that AppModel::beforeValidate() reports. rowExists now leaves an array to AppModel and
// still accepts none and an existing id and refuses an id that matches no row.
//
//   php tests/php/test_api_monitor_row_exists_array.php [repo-root]

class App { public static function uses($class, $package) {} }
class AppModel {
  public $id = false;
  public $data = array();
}
class FakeTable {
  public $alias;
  public $ids;
  public function __construct($alias, $ids) { $this->alias = $alias; $this->ids = $ids; }
  public function find($type, $options) {
    $id = $options['conditions'][$this->alias.'.Id'];
    if ($id === array()) {
      return array();
    }
    if (is_array($id)) {
      throw new PDOException("Unknown column 'Array' in 'where clause'");
    }
    return in_array((string)$id, $this->ids, true) ? array($this->alias => array('Id' => $id)) : array();
  }
}
class ClassRegistry {
  public static function init($name) {
    return new FakeTable($name, $name == 'Server' ? array('1', '4') : array('2'));
  }
}

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
require_once "$repoRoot/web/api/app/Model/Monitor.php";

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

$m = new Monitor();
foreach (array('ServerId' => array('1', '4', '77'), 'StorageId' => array('2', '2', '9')) as $field => $values) {
  $rule = isset($m->validate[$field]['rule']) ? $m->validate[$field]['rule'] : null;
  check($rule !== null, "Monitor validates $field");
  if ($rule === null) continue;
  $ok = function($value) use ($m, $rule, $field) {
    try {
      return $m->{$rule[0]}(array($field => $value), $rule[1]);
    } catch (PDOException $e) {
      return $e->getMessage();
    }
  };
  check($ok(null) === true, "$field null is accepted");
  check($ok('') === true, "$field empty is accepted");
  check($ok('0') === true, "$field 0 is accepted as none");
  check($ok(array('a' => array('b' => 1))) === true, "$field nested array is left to AppModel and not looked up");
  check($ok(array()) === false, "$field empty list still matches nothing");
  check($ok(array(1, 2)) === true, "$field list is left to AppModel and not looked up");
  check($ok($values[0]) === true, "$field $values[0] that exists is accepted");
  check($ok(end($values)) === false, "$field ".end($values)." that does not exist is refused");
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
