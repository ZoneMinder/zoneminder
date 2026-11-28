<?php
// Frame's Type validation used Cake's multiple rule, which array_filter()s the value with strlen and so throws a
// TypeError for a list that holds a list. POST /frames/add with Type[a][b]=1 was a 500. AppModel::beforeValidate()
// already refuses a list ("must be a single value"), so the rule only has to cope: a nested list is left to it,
// while an empty value still fails as before.
// The Cake model is stubbed and Cake's own Validation class is used, so this runs from a source checkout:
//   php tests/php/test_api_frame_type_nested.php

class App { public static function uses($class, $package) {} }
class AppModel {
  public $alias = '';
  public $validate = array();
}
$root = __DIR__.'/../..';
require_once $root.'/web/api/lib/Cake/Utility/Validation.php';
require_once $root.'/web/api/app/Model/Frame.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

// Whether every rule on Type accepts $value, run the way Cake runs it; a thrown error is returned by class.
function verdict($model, $value) {
  try {
    foreach ($model->validate['Type'] as $set) {
      $rule = (array)$set['rule'];
      $method = array_shift($rule);
      if (method_exists($model, $method)) $ok = $model->$method(array('Type' => $value), ...$rule);
      else $ok = Validation::$method($value, ...$rule);
      if ($ok !== true) return false;
    }
  } catch (Throwable $e) {
    return get_class($e);
  }
  return true;
}

$frame = new Frame();
check('a type is accepted', verdict($frame, 'Alarm'), true);
check('an empty type is refused', verdict($frame, ''), false);
check('no type is refused', verdict($frame, null), false);
check('an empty list is refused', verdict($frame, array()), false);
check('a list is left to AppModel', verdict($frame, array('Alarm')), true);
check('a nested list is not a TypeError', verdict($frame, array('a' => array('b' => '1'))), true);
check('a list holding a list is not a TypeError', verdict($frame, array(array('1'))), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
