<?php
// Tests FilterComponent::qualifyFields(), which MonitorsController::index()
// runs on its named parameters before buildFilter():
//   - a bare Monitors column is written Monitor.Field, so Id and Name are no
//     longer ambiguous with the joined tables
//   - a bare column of exactly one joined table (Status, CaptureFPS from
//     Monitor_Status) is left bare, as before; one that two joined tables
//     have (MonitorId) is a 400
//   - Model.Field must be a column of the queried or a joined model
//   - GroupId is left bare for the controller's Groups_Monitors join
//   - anything else is a 400 naming the field, instead of reaching MySQL as
//     an unknown column (a 500 that carried the SQL error)
//
// Cake's Component is stubbed, so this runs from a source checkout:
// php tests/php/test_api_monitor_filter_fields.php

class App {
  public static function uses($class, $package) {}
}
class Component {}
class BadRequestException extends Exception {}

require_once __DIR__.'/../../web/api/app/Controller/Component/FilterComponent.php';

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

// What a 400 says, or the conditions buildFilter() makes.
function run($named) {
  $f = new FilterComponent();
  $fields = array(
    'Monitor' => array('Id', 'Name', 'Type', 'Enabled', 'Deleted', 'Capturing', 'ServerId'),
    'Monitor_Status' => array('MonitorId', 'Status', 'CaptureFPS', 'AnalysisFPS'),
    'Event_Summary' => array('MonitorId', 'TotalEvents'),
    'Manufacturer' => array('Id', 'Name'),
  );
  try {
    return $f->buildFilter($f->qualifyFields($named, $fields, array('GroupId')));
  } catch (BadRequestException $e) {
    return '400 '.$e->getMessage();
  }
}

check('bare column is qualified',
  run(array('Name' => 'hikvision-1')), array('Monitor.Name' => 'hikvision-1'));
check('operator is kept',
  run(array('Id >' => '0')), array('Monitor.Id >' => '0'));
check('LIKE is kept',
  run(array('Name LIKE' => 'hik')), array('Monitor.Name LIKE' => '%hik%'));
check('IN is kept',
  run(array('Id IN' => '1,2')), array(array('Monitor.Id IN' => array('1', '2'))));
check('Monitor.Field is accepted',
  run(array('Monitor.Deleted' => '0')), array('Monitor.Deleted' => '0'));
check('column case follows the schema',
  run(array('enabled' => '1')), array('Monitor.Enabled' => '1'));
check('a joined model column is accepted',
  run(array('Manufacturer.Name' => 'Acme')), array('Manufacturer.Name' => 'Acme'));
check('GroupId stays bare',
  run(array('GroupId' => '3')), array('GroupId' => '3'));
check('paging parameters are skipped as before',
  run(array('Deleted' => '0', 'limit' => '5', 'page' => '2')), array('Monitor.Deleted' => '0'));
check('bare column of one joined table is left bare',
  run(array('Status' => 'Connected')), array('Status' => 'Connected'));
check('bare joined column with an operator',
  run(array('CaptureFPS >' => '0')), array('CaptureFPS >' => '0'));
check('bare joined column case follows the schema',
  run(array('analysisfps <' => '5')), array('AnalysisFPS <' => '5'));
check('bare joined column next to a Monitors column',
  run(array('Status' => 'Connected', 'Deleted' => '0')), array('Status' => 'Connected', 'Monitor.Deleted' => '0'));
check('bare column of two joined tables is a 400',
  run(array('MonitorId' => '1')), '400 Ambiguous filter field: MonitorId');
check('a joined model column can still be qualified',
  run(array('Manufacturer.Id' => '1')), array('Manufacturer.Id' => '1'));
check('unknown column is a 400',
  run(array('NoSuchField' => '1')), '400 Unknown filter field: NoSuchField');
check('unknown model is a 400',
  run(array('Event.Id' => '1')), '400 Unknown filter field: Event.Id');
check('column of the wrong model is a 400',
  run(array('Manufacturer.Type' => '1')), '400 Unknown filter field: Manufacturer.Type');
check('groupid in another case is not the join',
  run(array('groupid' => '1')), '400 Unknown filter field: groupid');
check('operator allow list still applies',
  run(array('Id OR' => '1')), '400 Invalid operator: OR');
check('invalid characters still rejected',
  run(array(';Id' => '1')), '400 Invalid argument before `:`: ;Id');
check('no named params',
  (new FilterComponent())->qualifyFields(array(), array('Monitor' => array('Id'))), array());

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
