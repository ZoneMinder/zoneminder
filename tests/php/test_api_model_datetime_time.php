<?php
// AppModel::beforeValidate() must refuse a time of day MySQL refuses, so a datetime such as
// 2026-01-31 25:00:00 is a field error and not a 500 from the insert.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_model_datetime_time.php

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Thing';
  public $data = array();
  public $invalid = array();
  public function schema() {
    return array(
      'StartDateTime' => array('type' => 'datetime'),
      'Stamp' => array('type' => 'timestamp'),
      'Day' => array('type' => 'date'),
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

foreach (array('2026-01-31 23:59:59', '2026-01-31 00:00:00', '2026-01-31 7:5:9', '2026-01-31T12:00',
    '2026-01-31 12:00:00.5', '2026-01-31 12:00') as $v) {
  check("datetime $v is accepted", accepted('StartDateTime', $v), true);
}
check('timestamp with a valid time is accepted', accepted('Stamp', '2026-01-31 12:30:00'), true);
check('date alone is accepted', accepted('Day', '2026-01-31'), true);

foreach (array('2026-01-31 24:00:00', '2026-01-31 25:00:00', '2026-01-31 12:60:00', '2026-01-31 12:61:00',
    '2026-01-31 12:00:60', '2026-01-31 12:00:99', '2026-01-31 99:99:99', '2026-02-30 12:00:00') as $v) {
  check("datetime $v is refused", accepted('StartDateTime', $v), false);
}
check('timestamp with hour 25 is refused', accepted('Stamp', '2026-01-31 25:00:00'), false);
check('date with a bad time is refused', accepted('Day', '2026-01-31 99:99:99'), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
