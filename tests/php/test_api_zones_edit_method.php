<?php
// GET /api/zones/edit/<id> must be refused: before the fix it fell through and saved an empty
// body, answering 200. ZonesController::edit() now throws BadRequestException for anything that
// is not POST or PUT, like MonitorsController::edit() does.
//
// ZonesController can't be loaded without CakePHP, so edit() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_zones_edit_method.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$file = $root.'/web/api/app/Controller/ZonesController.php';
if (!is_file($file)) {
  echo "FAIL - controller not found at $file\n";
  exit(1);
}
$source = file_get_contents($file);

eval('namespace ZM; function Error($m) {} function Warning($m) {}
class Monitor {
  function __construct($m = null) {}
  function canEdit() { return true; }
  function Type() { return "Ffmpeg"; }
  function Deleted() { return false; }
  function Capturing() { return "None"; }
  function Controllable() { return false; }
  function zmcControl($m) {}
}');

class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
function __($s) { return $s; }

class FakeModel {
  public $id, $primaryKey = 'Id', $saveResult = true, $saveCalls = 0, $savedData = null;
  function __construct($saveResult = true) { $this->saveResult = $saveResult; }
  function exists($id = null) { return true; }
  function create() {}
  function save($d = null) { $this->saveCalls++; $this->savedData = $d; return $this->saveResult; }
  function validates() { return $this->saveResult; }
  function invalidFields() { return array('Name' => 'is required'); }
  function find($t = null, $o = null) { return array('Zone' => array('MonitorId' => 1)); }
}

class FakeResponse {
  public $status = 200;
  function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; }
}

class FakeRequest {
  public $data = array('Zone' => array('Name' => 'x')), $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { foreach ((array)$t as $m) if (strtolower($m) == strtolower($this->method)) return true; return false; }
  function allowMethod() { return true; }
}

class HarnessBase {
  public $Zone, $request, $response, $out = array(), $pinCalls = 0;
  function __construct($method, $saveResult = true) {
    $this->request = new FakeRequest($method);
    $this->response = new FakeResponse;
    $this->Zone = new FakeModel($saveResult);
  }
  function pinRequestId($m, $id) { $this->pinCalls++; $m->id = $id; }
  function zoneMonitorId($id) { return 1; }
  function requireMonitorEdit($id) {}
  function requestMonitorId() { return null; }
  function set($a) { $this->out = $a; }
}

function methodSource($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  // The method's require_once paths are relative to its own file; the stubs above stand in.
  return preg_replace('/^\s*require_once .*$/m', '', substr($source, $start, $i - $start + 1))."\n";
}

eval('class ZonesEditHarness extends HarnessBase { '.methodSource($source, 'edit').' }');

global $user;
$user = null;

// GET must be refused before anything is saved.
$h = new ZonesEditHarness('get');
$thrown = 'none';
try { $h->edit(3); } catch (BadRequestException $e) { $thrown = 'BadRequestException'; } catch (Exception $e) { $thrown = get_class($e); }
check('GET edit throws BadRequestException', $thrown, 'BadRequestException');
check('GET edit does not call save()', $h->Zone->saveCalls, 0);
check('GET edit does not pin the request id', $h->pinCalls, 0);

// PUT with a successful save does not throw and answers the saved message.
$h = new ZonesEditHarness('put', true);
$thrown = 'none';
try { $h->edit(3); } catch (Exception $e) { $thrown = get_class($e); }
check('PUT edit does not throw', $thrown, 'none');
check('PUT edit answers saved message', isset($h->out['message']) ? $h->out['message'] : null, 'The zone has been saved.');
check('PUT edit calls save() once', $h->Zone->saveCalls, 1);
check('PUT edit serializes message', isset($h->out['_serialize']) ? $h->out['_serialize'] : null, array('message'));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
