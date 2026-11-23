<?php
// ZonesController must treat a monitor row with Monitor.Deleted = 1 as missing:
//   forMonitor() lists a monitor's zones and must answer NotFound for a deleted monitor.
//   add() must not save a zone onto a deleted monitor.
// forMonitor() and add() are taken from the source and run against stubs, so the same test
// works on the original copy of the controller (root is $argv[1]).
// Run as: php tests/php/test_api_zones_deleted_monitor.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
eval('namespace ZM; function Error($m) {} function Warning($m) {}
class Monitor {
  public $row;
  function __construct($m = null) { $this->row = $m; }
  function canView() { return true; }
  function canEdit() { return true; }
  function Deleted() { return !empty($GLOBALS["zm_monitor_deleted"]); }
  function Type() { return "Ffmpeg"; }
  function zmcControl($m) {}
}');
class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
function __($s) { return $s; }
class FakeModel {
  public $id, $recursive, $primaryKey = 'Id', $saves = 0;
  function exists($id = null) { return true; }
  function create() {}
  function save($d = null) {
    $this->saves++;
    return array('Zone' => array('MonitorId' => 1));
  }
  function validates() { return true; }
  function invalidFields() { return array(); }
  function find($t = null, $o = null) {
    if ($t == 'first') {
      return array('Monitor' => array(
        'Id' => 1,
        'Deleted' => empty($GLOBALS['zm_row_deleted']) ? 0 : 1
      ));
    }
    return array();
  }
}
class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data = array('Zone' => array('MonitorId' => 1)), $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { foreach ((array)$t as $m) if (strtolower($m) == strtolower($this->method)) return true; return false; }
}
class HarnessBase {
  public $request, $response, $set = array(), $models = array();
  function __construct($method) {
    $this->request = new FakeRequest($method);
    $this->response = new FakeResponse;
  }
  function __get($n) {
    if (!isset($this->models[$n])) $this->models[$n] = new FakeModel;
    return $this->models[$n];
  }
  function loadModel($n) {}
  function requireMonitorEdit($id) {}
  function requestMonitorId() {
    $data = $this->request->data;
    if (isset($data['Zone']) and is_array($data['Zone'])) $data = $data['Zone'];
    return isset($data['MonitorId']) ? $data['MonitorId'] : null;
  }
  function pinRequestId($m, $id) { $m->id = $id; }
  function set($a) { $this->set = $a; }
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
$source = file_get_contents($root.'/web/api/app/Controller/ZonesController.php');
eval('class ZonesHarness extends HarnessBase { '.methodSource($source, 'forMonitor').methodSource($source, 'add').' }');

// forMonitor(): a deleted monitor row is NotFound, and its zones are never listed.
$GLOBALS['zm_row_deleted'] = true;
$h = new ZonesHarness('GET');
try {
  $h->forMonitor(1);
  check('forMonitor deleted monitor throws NotFoundException', 'no exception', 'NotFoundException');
} catch (Throwable $e) {
  check('forMonitor deleted monitor throws NotFoundException', get_class($e), 'NotFoundException');
}
check('forMonitor deleted monitor lists no zones', isset($h->set['zones']), false);

// forMonitor(): a live monitor row is listed normally.
$GLOBALS['zm_row_deleted'] = false;
$h = new ZonesHarness('GET');
try {
  $h->forMonitor(1);
  check('forMonitor live monitor does not throw', true, true);
} catch (Throwable $e) {
  check('forMonitor live monitor does not throw', get_class($e).': '.$e->getMessage(), 'no exception');
}
check('forMonitor live monitor sets zones', array_key_exists('zones', $h->set), true);

// add(): a zone for a deleted monitor is refused before it is saved.
$GLOBALS['zm_monitor_deleted'] = true;
$h = new ZonesHarness('POST');
try {
  $h->add();
  check('add deleted monitor throws NotFoundException', 'no exception', 'NotFoundException');
} catch (Throwable $e) {
  check('add deleted monitor throws NotFoundException', get_class($e), 'NotFoundException');
}
check('add deleted monitor does not save the zone', $h->Zone->saves, 0);

// add(): a zone for a live monitor is saved.
$GLOBALS['zm_monitor_deleted'] = false;
$h = new ZonesHarness('POST');
try {
  $h->add();
  check('add live monitor does not throw', true, true);
} catch (Throwable $e) {
  check('add live monitor does not throw', get_class($e).': '.$e->getMessage(), 'no exception');
}
check('add live monitor saves the zone', $h->Zone->saves, 1);
check('add live monitor answers Saved', isset($h->set['message']) ? $h->set['message'] : null, 'Saved');

unset($GLOBALS['zm_row_deleted'], $GLOBALS['zm_monitor_deleted']);
exit($failures ? 1 : 0);
