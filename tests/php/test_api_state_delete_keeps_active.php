<?php
// Deleting the active run state through the API must leave a state active, as the console's
// delete does: the default state becomes active, and an inactive state's delete changes nothing.
// The Cake model is stubbed over an in-memory States table, so this runs from a source checkout:
//   php tests/php/test_api_state_delete_keeps_active.php [repo root]

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'State';
  public $id = null;
  public static $rows = array();
  public function find($type, $options = array()) {
    $count = 0;
    foreach (self::$rows as $row) {
      foreach ($options['conditions'] as $field => $value) {
        if ($row[substr($field, strlen($this->alias) + 1)] != $value) continue 2;
      }
      $count++;
    }
    return $count;
  }
  public function updateAll($fields, $conditions) {
    foreach (self::$rows as &$row) {
      foreach ($conditions as $field => $value) {
        if ($row[substr($field, strlen($this->alias) + 1)] != $value) continue 2;
      }
      foreach ($fields as $field => $value) $row[substr($field, strlen($this->alias) + 1)] = $value;
    }
    return true;
  }
  // Cake removes the row, then calls afterDelete().
  public function delete($id) {
    unset(self::$rows[$id]);
    $this->afterDelete();
    return true;
  }
  public function afterDelete() {}
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/api/app/Model/AppModel.php';
require_once $root.'/web/api/app/Model/State.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

function active() {
  $names = array();
  foreach (Model::$rows as $row) if ($row['IsActive']) $names[] = $row['Name'];
  return $names;
}

$state = new State();

Model::$rows = array(1 => array('Name' => 'default', 'IsActive' => 0), 2 => array('Name' => 'night', 'IsActive' => 1));
$state->delete(2);
check('deleting the active state makes default active', active(), array('default'));

Model::$rows = array(1 => array('Name' => 'default', 'IsActive' => 1), 2 => array('Name' => 'night', 'IsActive' => 0));
$state->delete(2);
check('deleting an inactive state keeps default active', active(), array('default'));

Model::$rows = array(1 => array('Name' => 'default', 'IsActive' => 0), 2 => array('Name' => 'night', 'IsActive' => 1), 3 => array('Name' => 'day', 'IsActive' => 0));
$state->delete(3);
check('deleting an inactive state leaves the active one alone', active(), array('night'));

Model::$rows = array(2 => array('Name' => 'night', 'IsActive' => 1));
$state->delete(2);
check('with no default state none becomes active', active(), array());

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
