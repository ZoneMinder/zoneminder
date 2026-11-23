<?php
// ConfigsController::delete() must refuse system and readonly Config entries even
// when the caller may edit, and must not reach the delete itself. delete() is
// taken from the source and run against stubs.
// Run as: php tests/php/test_api_configs_delete_protected.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$user = null;
class ForbiddenException extends Exception {}
class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }
class FakeModel {
  public $id, $primaryKey = 'Name', $sys = 0, $ro = 0, $deleteCalls = 0;
  function __construct($sys, $ro) { $this->sys = $sys; $this->ro = $ro; }
  function exists($id = null) { return true; }
  function find($t = null, $o = null) {
    return array('Config' => array('Name' => 'ZM_X', 'System' => $this->sys, 'Readonly' => $this->ro));
  }
  function delete($id = null) { $this->deleteCalls++; return true; }
}
class FakeRequest {
  function allowMethod() { return true; }
}
class HarnessBase {
  public $Config, $set = array(), $request;
  function __construct($sys, $ro) {
    $this->Config = new FakeModel($sys, $ro);
    $this->request = new FakeRequest;
  }
  function set($a) { $this->set = $a; }
  function pinRequestId($m, $id) { $m->id = $id; }
}
function methodSource($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  if ($start === false) return '';
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  // The method's require_once paths are relative to its own file; the stubs above stand in.
  return preg_replace('/^\s*require_once .*$/m', '', substr($source, $start, $i - $start + 1))."\n";
}
$source = file_get_contents($root.'/web/api/app/Controller/ConfigsController.php');
$method = methodSource($source, 'delete');
if (strpos($method, 'public function delete(') === false) {
  echo "FAIL - could not extract delete() from ConfigsController.php\n";
  exit(1);
}
eval('class Harness extends HarnessBase { '.$method.' }');
$cases = array(
  array('system entry', 1, 0),
  array('readonly entry', 0, 1),
  array('editable entry', 0, 0),
);
foreach ($cases as $case) {
  list($name, $sys, $ro) = $case;
  $h = new Harness($sys, $ro);
  $threw = null;
  try { $h->delete(1); } catch (Throwable $e) { $threw = $e; }
  if ($sys || $ro) {
    check("$name throws ForbiddenException", $threw instanceof ForbiddenException, true);
    check("$name does not call delete()", $h->Config->deleteCalls, 0);
  } else {
    check("$name does not throw", $threw === null, true);
    check("$name calls delete() once", $h->Config->deleteCalls, 1);
    check("$name answers Deleted", isset($h->set['message']) ? $h->set['message'] : null, 'Deleted');
  }
}
exit($failures ? 1 : 0);
