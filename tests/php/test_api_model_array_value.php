<?php
// AppModel::beforeValidate() must refuse a list or object sent for a plain column. Cake builds the
// insert with the word Array in place of the value and MySQL answers "Unknown column 'Array'",
// a 500, so it has to be a field error instead.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_model_array_value.php

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Thing';
  public $data = array();
  public $invalid = array();
  public function schema() {
    return array(
      'Name' => array('type' => 'string', 'length' => 5),
      'Width' => array('type' => 'integer'),
      'Notes' => array('type' => 'text'),
      'StartDateTime' => array('type' => 'datetime'),
      'Method' => array('type' => "enum('Local','Remote')"),
    );
  }
  public function invalidate($field, $message) { $this->invalid[$field] = $message; }
}
require_once __DIR__.'/../../web/api/app/Model/AppModel.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

function accepted($field, $value, $extra = array()) {
  $m = new AppModel();
  $m->data = array('Thing' => array_merge(array($field => $value), $extra));
  $m->beforeValidate();
  return !isset($m->invalid[$field]);
}

foreach (array('Name', 'Width', 'Notes', 'StartDateTime', 'Method') as $field) {
  check("$field as a list is refused", accepted($field, array('a', 'b')), false);
  check("$field as an object is refused", accepted($field, array('a' => 'b')), false);
  check("$field as a nested list is refused", accepted($field, array(array(1))), false);
}
check('Name as a string is accepted', accepted('Name', 'abc'), true);
check('Width as a number is accepted', accepted('Width', 640), true);
check('Notes as text is accepted', accepted('Notes', 'free text'), true);
check('an empty list is left to Cake, which saves NULL', accepted('Name', array()), true);
check('an array for a key that is not a column is left alone', accepted('Name', 'abc', array('Event' => array('Event' => array(1, 2)))), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
