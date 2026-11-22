<?php
// Editing only the Coords of an existing Percent zone must still write them as percentages
// (with decimals), because zmc reads decimal-less Coords as pixels whatever Units says.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_zone_edit_keeps_units.php [root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
class App { public static function uses($class, $package) {} }
class AppModel {
  public $alias = 'Zone';
  public $id = null;
  public $data = array();
  public $Monitor;
  public $stored = array('Units' => 'Percent', 'MonitorId' => 7);
  public function field($f) { return $this->stored[$f]; }
}
require_once $root.'/web/api/app/Model/Zone.php';

class StubMonitor {
  public function find($type, $options) {
    return array('Monitor' => array('Id' => 7, 'Width' => 1000, 'Height' => 500));
  }
}

$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) echo "ok - $name\n";
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

function save($data, $stored) {
  $z = new Zone();
  $z->Monitor = new StubMonitor();
  $z->id = 3;
  $z->stored = $stored;
  $z->data = array('Zone' => $data);
  $z->beforeSave();
  return $z->data['Zone'];
}

$r = save(array('Coords' => '0,0 50,0 0,50'), array('Units' => 'Percent', 'MonitorId' => 7));
check('edit of a Percent zone writes decimals', $r['Coords'], '0.00,0.00 50.00,0.00 0.00,50.00');

$r = save(array('Coords' => '0,0 50,0 0,50'), array('Units' => 'Pixels', 'MonitorId' => 7));
check('edit of a Pixels zone keeps pixel Coords', $r['Coords'], '0,0 50,0 0,50');

$r = save(array('Coords' => '0,0 50,0 0,50', 'Units' => 'Pixels'), array('Units' => 'Percent', 'MonitorId' => 7));
check('explicit Units wins', $r['Coords'], '0,0 50,0 0,50');

exit($failures ? 1 : 0);
