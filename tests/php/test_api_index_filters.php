<?php
// index() of the zones, zone presets and monitors API controllers:
//   - named parameters (/zones/index/MonitorId:3.json, /zone_presets/index/Name:x.json) filter
//     the rows; they used to be ignored and every row came back
//   - /monitors/index/sort:Name/direction:desc.json orders the monitors; sort and direction
//     are removed from the filter and were never applied
// The controllers can't be loaded without CakePHP, so index() is taken from the source and run
// in a stand-in with a recording model.
//   php tests/php/test_api_index_filters.php

class App { public static function uses($class, $package) {} }
class Component {}
class BadRequestException extends Exception {}
require_once __DIR__.'/../../web/api/app/Controller/Component/FilterComponent.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".json_encode($got)." expected ".json_encode($expected).")\n"; }
}

function method($file, $name) {
  $src = file_get_contents(__DIR__.'/../../web/api/app/Controller/'.$file);
  if (!preg_match('/^  public function '.$name.'\(.*?^  }\n/ms', $src, $m)) { echo "not ok - no $name in $file\n"; exit(1); }
  // The monitors index loads the ZM\Monitor class from the web tree; a stub is defined below
  return preg_replace('/require_once[^;]*;/', '', $m[0]);
}

class RecModel {
  public $recursive = 0;
  public $found = null;
  public $cols;
  public $hasOne = array();
  public $belongsTo = array();
  function __construct($cols) { $this->cols = $cols; }
  function schema() { return array_combine($this->cols, $this->cols); }
  function find($type, $options = array()) { $this->found = $options; return array(); }
}
class Loader {
  function load($n) { return new FilterComponent(); }
}
class Req {
  public $params = array('named' => array());
  public $q = array();
  function query($k) { return isset($this->q[$k]) ? $this->q[$k] : null; }
}
class RecUser {
  function unviewableMonitorIds() { return true; }
  function viewableMonitorIds() { return array(1, 2); }
}
class Obj {
  public $Components;
  public $request;
  function __construct() { $this->Components = new Loader(); $this->request = new Req(); }
  function set($a) { $this->set = $a; }
}
eval('class ZonesH extends Obj { public $Zone; '.str_replace('public function', 'public function', method('ZonesController.php', 'index')).' }');
eval('class PresetsH extends Obj { public $ZonePreset; '.method('ZonePresetsController.php', 'index').' }');
eval('class MonitorsH extends Obj { public $Monitor; '.method('MonitorsController.php', 'index').' }');
eval('namespace ZM { class Monitor { function __construct($r) {} function canView() { return true; } } }');

function run($class, $prop, $cols, $named, $query = array(), $user = null) {
  global $user;
  $h = new $class();
  $h->$prop = new RecModel($cols);
  $h->request->params['named'] = $named;
  $h->request->q = $query;
  try {
    $h->index();
  } catch (BadRequestException $e) {
    return '400 '.$e->getMessage();
  }
  return $h->$prop->found;
}

$user = null;
$zcols = array('Id', 'MonitorId', 'Name', 'Type');
check('zones: no filter', run('ZonesH', 'Zone', $zcols, array()), array());
check('zones: MonitorId filter', run('ZonesH', 'Zone', $zcols, array('MonitorId' => '3')),
  array('conditions' => array('MonitorId' => '3')));
check('zones: unknown field is a 400', run('ZonesH', 'Zone', $zcols, array('Bogus' => '3')),
  '400 Unknown filter field: Bogus');
$user = new RecUser();
check('zones: ACL kept next to the filter', run('ZonesH', 'Zone', $zcols, array('Name' => 'x')),
  array('conditions' => array('Name' => 'x', array('Zone.MonitorId' => array(1, 2)))));
$user = null;

check('presets: no filter', run('PresetsH', 'ZonePreset', array('Id', 'Name'), array()), array());
check('presets: Name filter', run('PresetsH', 'ZonePreset', array('Id', 'Name'), array('Name' => 'x')),
  array('conditions' => array('Name' => 'x')));

$mcols = array('Id', 'Name', 'Deleted');
function order($r) { return (is_array($r) && isset($r['order'])) ? $r['order'] : 'none'; }
check('monitors: no sort', order(run('MonitorsH', 'Monitor', $mcols, array())), 'none');
check('monitors: sort and direction as named params',
  order(run('MonitorsH', 'Monitor', $mcols, array('sort' => 'Name', 'direction' => 'desc'))), array('Monitor.Name' => 'desc'));
check('monitors: sort in the query string defaults to asc',
  order(run('MonitorsH', 'Monitor', $mcols, array(), array('sort' => 'Name'))), array('Monitor.Name' => 'asc'));
check('monitors: sort does not become a filter',
  isset(run('MonitorsH', 'Monitor', $mcols, array('sort' => 'Name'))['conditions']['Monitor.sort']), false);
check('monitors: unknown sort field is a 400',
  run('MonitorsH', 'Monitor', $mcols, array('sort' => 'Nope; DROP')), '400 Unknown sort field');
check('monitors: bad direction is a 400',
  run('MonitorsH', 'Monitor', $mcols, array('sort' => 'Name', 'direction' => 'sideways')), '400 direction must be asc or desc');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
