<?php
// DELETE /api/monitors/<id> on a monitor that is already marked Deleted answered 200 "Deleted"
// again (and stopped zmc and rebuilt the crontab a second time). It now answers 404 like an
// unknown monitor. The first delete still goes through.
//
// MonitorsController can't be loaded without CakePHP, so delete() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_monitor_delete_twice.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/MonitorsController.php');
$start = strpos($source, 'public function delete(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);
// The real ZM\Monitor is not loaded; the stub below stands in for it.
$empty = tempnam(sys_get_temp_dir(), 'zm');
$method = str_replace('__DIR__ .\'/../../../includes/Monitor.php\'', var_export($empty, true), $method);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

eval('namespace ZM; class Monitor {
  static $deleted = array(); static $calls = 0;
  private $id;
  function __construct($id) { $this->id = $id; }
  function Deleted() { return !empty(self::$deleted[$this->id]); }
  function delete() { self::$calls++; self::$deleted[$this->id] = 1; return true; }
}');

class FakeModel {
  public $id;
  function exists() { return true; }
}
class FakeRequest { function allowMethod() {} }
eval('class FakeController { public $Monitor; public $request; public $out;
  function set($a) { $this->out = $a; }
  '.$method.' }');

$c = new FakeController();
$c->Monitor = new FakeModel();
$c->request = new FakeRequest();
$user = null;

$c->delete(7);
check('first delete answers Deleted', $c->out['message'], 'Deleted');
check('first delete deletes the monitor', ZM\Monitor::$calls, 1);

$c->out = null;
try {
  $c->delete(7);
  check('second delete is refused', 'answered', 'NotFoundException');
} catch (NotFoundException $e) {
  check('second delete is refused', 'NotFoundException', 'NotFoundException');
}
check('second delete does not run the delete again', ZM\Monitor::$calls, 1);

unlink($empty);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
