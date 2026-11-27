<?php
// Monitors.ManufacturerId and Monitors.ModelId are foreign keys to Manufacturers and Models. An API
// monitor add or edit with an id that matches no row reached MySQL, which refused the write, and the
// client got a 500. The Monitor model now refuses it as a validation error and still accepts none and
// an existing id.
//
//   php tests/php/test_api_monitor_reference_ids.php [repo-root]

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
    return in_array((string)$id, $this->ids, true) ? array($this->alias => array('Id' => $id)) : array();
  }
}
class ClassRegistry {
  public static function init($name) {
    return $name == 'Manufacturer' ? new FakeTable('Manufacturer', array('1', '4')) : new FakeTable('CameraModel', array('2'));
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
foreach (array('ManufacturerId' => array('1', '4', '77'), 'ModelId' => array('2', '9')) as $field => $values) {
  $rule = isset($m->validate[$field]['rule']) ? $m->validate[$field]['rule'] : null;
  check($rule !== null, "Monitor validates $field");
  if ($rule === null) continue;
  $ok = function($value) use ($m, $rule, $field) { return $m->{$rule[0]}(array($field => $value), $rule[1]); };
  check($ok(null) === true, "$field null is accepted");
  check($ok('') === true, "$field empty is accepted");
  check($ok(array('a' => array('b' => 1))) === true, "$field nested array is left to AppModel and not looked up");
  check($ok($values[0]) === true, "$field $values[0] that exists is accepted");
  check($ok(end($values)) === false, "$field ".end($values)." that does not exist is refused");
  check($ok('0') === false, "$field 0 is refused, no row has that Id");
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
