<?php
// Validation rules in the API models that the stock Cake rules cannot take a list for. AppModel::beforeValidate()
// marks a list or object sent for a column invalid, but Cake still runs the field's other rules on it, so a
// custom rule has to cope: State Definition (maxLength) threw a TypeError, and CameraModel and EncoderTemplate
// put the list in a find() that MySQL answered with "Unknown column 'Array'". All were a 500. The rules now leave
// a list to AppModel; plain values are checked as before.
// The Cake model is stubbed and Cake's own Validation class is used, so this runs from a source checkout:
//   php tests/php/test_api_model_rules_list.php

class App { public static function uses($class, $package) {} }
class AppModel {
  public $alias = '';
  public $id = null;
  public $data = array();
  public $stored = array();
  public $validate = array();
  public function field($name) { return isset($this->stored[$name]) ? $this->stored[$name] : null; }
  // Cake writes a nested array into the SQL as the word Array, and MySQL refuses it.
  public function find($type, $options) {
    foreach ($options['conditions'] as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $inner) if (is_array($inner)) throw new Exception("Unknown column 'Array' in 'WHERE'");
      }
    }
    return 0;
  }
}
$root = __DIR__.'/../..';
require_once $root.'/web/api/lib/Cake/Utility/Validation.php';
foreach (array('State', 'CameraModel', 'EncoderTemplate') as $model) {
  require_once $root.'/web/api/app/Model/'.$model.'.php';
}

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

// Whether every rule on $field (only the one named $only, if given) accepts $value, run the way Cake runs it;
// a thrown error is returned by class.
function verdict($model, $field, $value, $only = null) {
  $model->data[$model->alias][$field] = $value;
  try {
    foreach ($model->validate[$field] as $set) {
      $rule = (array)$set['rule'];
      $method = array_shift($rule);
      if ($only !== null and $method !== $only) continue;
      if (method_exists($model, $method)) $ok = $model->$method(array($field => $value), ...$rule);
      else $ok = Validation::$method($value, ...$rule);
      if ($ok !== true) return false;
    }
  } catch (Throwable $e) {
    return get_class($e);
  }
  return true;
}

$list = array('a', 'b');
$nested = array(array('a'));

$state = new State();
$state->alias = 'State';
check('State Definition: text is accepted', verdict($state, 'Definition', 'cam1:Monitor'), true);
check('State Definition: a list is not a TypeError', verdict($state, 'Definition', $list), true);
check('State Definition: a nested array is not a TypeError', verdict($state, 'Definition', $nested), true);
check('State Definition: text over 65535 characters is refused', verdict($state, 'Definition', str_repeat('a', 65536)), false);
check('State Definition: text of 65535 characters is accepted', verdict($state, 'Definition', str_repeat('a', 65535)), true);

$model = new CameraModel();
$model->alias = 'CameraModel';
$model->data['CameraModel'] = array('Name' => 'X1', 'ManufacturerId' => 3);
check('CameraModel: a name and manufacturer are checked', verdict($model, 'ManufacturerId', 3), true);
check('CameraModel: a nested ManufacturerId is not a SQL error', verdict($model, 'ManufacturerId', $nested), true);
$model->data['CameraModel'] = array('Name' => $nested, 'ManufacturerId' => 3);
check('CameraModel: a nested Name is not a SQL error', verdict($model, 'Name', $nested, 'isUniquePerManufacturer'), true);

$template = new EncoderTemplate();
$template->alias = 'EncoderTemplate';
$template->data['EncoderTemplate'] = array('Encoder' => 'libx264', 'Name' => 'fast');
check('EncoderTemplate: an Encoder and Name are checked', verdict($template, 'Name', 'fast'), true);
$template->data['EncoderTemplate'] = array('Encoder' => $nested, 'Name' => 'fast');
check('EncoderTemplate: a nested Encoder is not a SQL error', verdict($template, 'Encoder', $nested, 'isUniqueByEncoder'), true);
$template->data['EncoderTemplate'] = array('Encoder' => 'libx264', 'Name' => $nested);
check('EncoderTemplate: a nested Name is not a SQL error', verdict($template, 'Name', $nested, 'isUniqueByEncoder'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
