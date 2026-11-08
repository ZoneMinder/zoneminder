<?php
// Security regression test: AppController::requestField() must read a field from the request
// the way CakePHP's Model::set() will save it. refs GHSA-fp33-8fx5-4j6c
//
// Model::set() treats the bare top-level fields as the model's data when the request's
// Model entry is empty (empty($data[alias]) -> _setAliasData()). requestField() read
// Model[field] whenever Model was an array, so {"Monitor":[],"EventEndCommand":"x"} passed
// the System-edit check on the command fields (no value found) while save() stored x. The
// same shape slipped past every check built on requestField(): Event.MonitorId,
// Frame.EventId, Group.ParentId.
//
// AppController can't be loaded without CakePHP, so the method is taken from the source and
// run in a stand-in with the same $this->request->data.
//
// Run as: php tests/php/test_api_request_field.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/AppController.php');
if (!preg_match('/^  protected function requestField\(.*?^  }\n/ms', $source, $m)) {
  echo "FAIL - could not find requestField in AppController.php\n";
  exit(1);
}
eval('class RequestFieldHarness { public $request; '.str_replace('protected function', 'public function', $m[0]).' }');

function field(array $data, $alias, $name) {
  $h = new RequestFieldHarness();
  $h->request = (object)array('data' => $data);
  return $h->requestField($alias, $name);
}

check('nested field is read',
  field(array('Monitor' => array('EventEndCommand' => 'x')), 'Monitor', 'EventEndCommand'), 'x');
check('bare field is read when there is no Model entry',
  field(array('EventEndCommand' => 'x'), 'Monitor', 'EventEndCommand'), 'x');
check('bare field is read when the Model entry is empty (Cake saves it)',
  field(array('Monitor' => array(), 'EventEndCommand' => 'x'), 'Monitor', 'EventEndCommand'), 'x');
check('bare field is read when the Model entry is an empty string (Cake saves it)',
  field(array('Monitor' => '', 'EventEndCommand' => 'x'), 'Monitor', 'EventEndCommand'), 'x');
check('bare field is ignored when the Model entry has data (Cake ignores it)',
  field(array('Monitor' => array('Name' => 'a'), 'EventEndCommand' => 'x'), 'Monitor', 'EventEndCommand'), null);
check('nested field wins over a bare one',
  field(array('Monitor' => array('EventEndCommand' => 'n'), 'EventEndCommand' => 'b'), 'Monitor', 'EventEndCommand'), 'n');
check('a non-array Model entry with data yields nothing (Cake skips it)',
  field(array('Monitor' => 'junk', 'EventEndCommand' => 'x'), 'Monitor', 'EventEndCommand'), null);
check('Event MonitorId behind an empty Event entry is read',
  field(array('Event' => array(), 'MonitorId' => '7'), 'Event', 'MonitorId'), '7');
check('missing field is null', field(array('Monitor' => array('Name' => 'a')), 'Monitor', 'EventEndCommand'), null);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
