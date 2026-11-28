<?php
// ZonesController::requestMonitorId() returned whatever the request held in
// MonitorId, so an array or object passed on to the permission check as if it
// were a monitor row and was then saved as the zone's MonitorId. It must be a
// plain value, and anything else is a bad request.
//
// Run as: php tests/php/test_api_zone_monitorid_scalar.php [root]

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

class BadRequestException extends Exception {}
function __($s) { return $s; }

$src = file_get_contents($root.'/web/api/app/Controller/ZonesController.php');
if (!preg_match('/^  private function requestMonitorId\(\) \{.*?^  \}\n/ms', $src, $m)) {
  echo "FAIL - cannot extract requestMonitorId()\n";
  exit(1);
}
eval('class Harness { public $request; '.$m[0].' public function call() { return $this->requestMonitorId(); } }');

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function ask($data) {
  $h = new Harness();
  $h->request = new stdClass();
  $h->request->data = $data;
  try {
    return $h->call();
  } catch (BadRequestException $e) {
    return 'bad request';
  }
}

check('a plain MonitorId is returned', ask(array('MonitorId' => '5')), '5');
check('a MonitorId inside Zone is returned', ask(array('Zone' => array('MonitorId' => '5'))), '5');
check('no MonitorId gives null', ask(array('Name' => 'x')), null);
check('an array MonitorId is a bad request', ask(array('MonitorId' => array('Id' => 5))), 'bad request');
check('an array MonitorId inside Zone is a bad request', ask(array('Zone' => array('MonitorId' => array('Id' => 5)))), 'bad request');
exit($failures ? 1 : 0);
