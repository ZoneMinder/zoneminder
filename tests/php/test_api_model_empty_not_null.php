<?php
// AppModel::beforeValidate() must refuse an empty string for a number, date or enum column that is
// NOT NULL. Cake sends '' to MySQL, which answers "Incorrect integer value: ''", a 500, so it has to
// be a field error instead. A nullable column still takes '' as NULL, and a text column keeps ''.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_model_empty_not_null.php

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Thing';
  public $primaryKey = 'Id';
  public $data = array();
  public $invalid = array();
  public function schema() {
    return array(
      'Id' => array('type' => 'integer', 'null' => false),
      'Height' => array('type' => 'integer', 'null' => false),
      'Width' => array('type' => 'integer', 'null' => true),
      'Big' => array('type' => 'biginteger', 'null' => false),
      'Tiny' => array('type' => 'tinyinteger', 'null' => false),
      'Ratio' => array('type' => 'float', 'null' => false),
      'Seen' => array('type' => 'datetime', 'null' => false),
      'Day' => array('type' => 'date', 'null' => false),
      'Stamp' => array('type' => 'timestamp', 'null' => false),
      'Ended' => array('type' => 'datetime', 'null' => true),
      'Method' => array('type' => "enum('Local','Remote')", 'null' => false),
      'Mode' => array('type' => "enum('Local','Remote')", 'null' => true),
      'Name' => array('type' => 'string', 'length' => 5, 'null' => false),
      'Notes' => array('type' => 'text', 'null' => false),
      'Enabled' => array('type' => 'boolean', 'null' => false),
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

function accepted($field, $value) {
  $m = new AppModel();
  $m->data = array('Thing' => array($field => $value));
  $m->beforeValidate();
  return !isset($m->invalid[$field]);
}

foreach (array('Height', 'Big', 'Tiny', 'Ratio', 'Seen', 'Day', 'Stamp', 'Method') as $field) {
  check("empty $field (NOT NULL) is refused", accepted($field, ''), false);
}
foreach (array('Width', 'Ended', 'Mode') as $field) {
  check("empty $field (nullable) is accepted", accepted($field, ''), true);
}
foreach (array('Name', 'Notes', 'Enabled') as $field) {
  check("empty $field (text or boolean) is accepted", accepted($field, ''), true);
}
check('empty Id (primary key, dropped by Cake on create) is accepted', accepted('Id', ''), true);
check('Height 0 is accepted', accepted('Height', 0), true);
check('Height "0" is accepted', accepted('Height', '0'), true);
check('Seen with a date is accepted', accepted('Seen', '2026-01-31 12:00:00'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
