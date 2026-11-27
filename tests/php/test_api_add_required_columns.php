<?php
// Manufacturers.Name, Models.Name and EncoderTemplates.Encoder/Name/Params are NOT NULL with no
// default. An API add without one reached MySQL, which refused the INSERT, and
// the client got a 500. Each must be required on create (and only on create, so
// an edit may leave it out), which turns the request into a validation error.
//
// Source check against db/zm_create.sql.in:
//   php tests/php/test_api_add_required_columns.php

class App { public static function uses($class, $package) {} }
class AppModel {}

$root = __DIR__.'/../..';
foreach (array('CameraModel', 'EncoderTemplate', 'Manufacturer') as $c) require_once $root.'/web/api/app/Model/'.$c.'.php';
$schema = file_get_contents($root.'/db/zm_create.sql.in');

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

$cases = array(
  array('Manufacturer', 'Manufacturers', 'Name'),
  array('CameraModel', 'Models', 'Name'),
  array('EncoderTemplate', 'EncoderTemplates', 'Encoder'),
  array('EncoderTemplate', 'EncoderTemplates', 'Name'),
  array('EncoderTemplate', 'EncoderTemplates', 'Params'),
);
foreach ($cases as $case) {
  list($class, $table, $field) = $case;
  $model = new $class();
  preg_match('/CREATE TABLE `'.$table.'` \((.*?)\n\)/s', $schema, $m);
  $column = preg_match('/^\s*`'.$field.'`[^\n]*$/m', $m[1], $col) ? $col[0] : '';
  check(stripos($column, 'NOT NULL') !== false and stripos($column, 'default') === false,
    "$table.$field is NOT NULL with no default");
  $required = array();
  foreach (isset($model->validate[$field]) ? $model->validate[$field] : array() as $rule) {
    if (isset($rule['required'])) $required[] = $rule['required'];
  }
  check(in_array('create', $required, true), "$class requires $field on create");
  check(!in_array(true, $required, true), "$class does not require $field on edit");
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
