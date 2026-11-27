<?php
// AppModel::beforeValidate() must refuse a whole number the column cannot hold, so a Colours of 300
// in a tinyint unsigned column is a field error and not a 500 "Out of range value" from the insert.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_model_integer_range.php

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Thing';
  public $data = array();
  public $invalid = array();
  public function schema() {
    return array(
      'Tiny' => array('type' => 'tinyinteger', 'unsigned' => false),
      'TinyU' => array('type' => 'tinyinteger', 'unsigned' => true),
      'Small' => array('type' => 'smallinteger', 'unsigned' => false),
      'SmallU' => array('type' => 'smallinteger', 'unsigned' => true),
      'Int' => array('type' => 'integer', 'unsigned' => false),
      'IntU' => array('type' => 'integer', 'unsigned' => true),
      'BigU' => array('type' => 'biginteger', 'unsigned' => true),
      'NoFlag' => array('type' => 'integer'),
      'Ratio' => array('type' => 'float', 'unsigned' => true),
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

$cases = array(
  array('TinyU', '0', true), array('TinyU', '255', true), array('TinyU', '256', false), array('TinyU', '300', false),
  array('TinyU', '-1', false), array('TinyU', 300, false), array('TinyU', '255.4', true), array('TinyU', '255.5', false),
  array('Tiny', '127', true), array('Tiny', '-128', true), array('Tiny', '128', false), array('Tiny', '-129', false),
  array('SmallU', '65535', true), array('SmallU', '65536', false), array('SmallU', '-1', false),
  array('Small', '32767', true), array('Small', '-32768', true), array('Small', '32768', false), array('Small', '-32769', false),
  array('IntU', '4294967295', true), array('IntU', '4294967296', false), array('IntU', '99999999999', false), array('IntU', '-1', false),
  array('Int', '2147483647', true), array('Int', '-2147483648', true), array('Int', '2147483648', false), array('Int', '-2147483649', false),
  array('Int', '1e3', true), array('Int', '1e10', false), array('Int', '-1', true),
  array('BigU', '18446744073709551615', true), array('BigU', '-1', false),
  array('NoFlag', '4294967296', false), array('NoFlag', '-5', true),
  array('Ratio', '1.5', true),
);
foreach ($cases as $c) {
  check($c[0].' = '.var_export($c[1], true).' is '.($c[2] ? 'accepted' : 'refused'), accepted($c[0], $c[1]), $c[2]);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
