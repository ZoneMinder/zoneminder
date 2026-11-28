<?php
// AppModel::beforeValidate() must refuse a decimal the column cannot hold, so an Event Latitude of 150
// (DECIMAL(10,8)), a Monitor MaxFPS of 1000 (DECIMAL(5,2)) or a negative zone threshold (DECIMAL(10,2)
// unsigned) is a field error and not a 500 "Out of range value" from the insert.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_model_decimal_range.php

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Thing';
  public $data = array();
  public $invalid = array();
  public function schema() {
    return array(
      'Latitude' => array('type' => 'decimal', 'length' => '10,8'),
      'Longitude' => array('type' => 'decimal', 'length' => '11,8'),
      'MaxFPS' => array('type' => 'decimal', 'length' => '5,2'),
      'CpuLoad' => array('type' => 'decimal', 'length' => '5,1'),
      'Threshold' => array('type' => 'decimal', 'length' => '10,2', 'unsigned' => true),
      'Whole' => array('type' => 'decimal', 'length' => '5'),
      'NoLength' => array('type' => 'decimal'),
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
  array('Latitude', '45.12345678', true), array('Latitude', '-90', true), array('Latitude', 99.99999999, true),
  array('Latitude', '100', false), array('Latitude', '150.5', false), array('Latitude', '-100', false),
  array('Latitude', '99.999999999', false),
  array('Longitude', '-180', true), array('Longitude', '180.5', true), array('Longitude', '1000', false),
  array('MaxFPS', '999.99', true), array('MaxFPS', '30', true), array('MaxFPS', '999.994', true),
  array('MaxFPS', '999.995', false), array('MaxFPS', '1000', false), array('MaxFPS', '-1', true), array('MaxFPS', '-1000', false),
  array('CpuLoad', '9999.9', true), array('CpuLoad', '10000', false),
  array('Threshold', '0', true), array('Threshold', '99999999.99', true), array('Threshold', '100000000', false),
  array('Threshold', '-1', false), array('Threshold', '-0.001', false), array('Threshold', '1e9', false),
  array('Whole', '99999', true), array('Whole', '100000', false), array('Whole', '99999.4', true),
  array('NoLength', '123456789012', true),
);
foreach ($cases as $c) {
  check($c[0].' = '.var_export($c[1], true).' is '.($c[2] ? 'accepted' : 'refused'), accepted($c[0], $c[1]), $c[2]);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
