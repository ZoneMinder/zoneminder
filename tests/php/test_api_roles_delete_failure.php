<?php
// RolesController::delete() must answer InternalErrorException (500) when the delete fails,
// instead of reporting success. It sets 'The role has been deleted.' when the delete works.
//
// RolesController can't be loaded without CakePHP, so delete() is taken from the source and
// run against stubs.
// Run as: php tests/php/test_api_roles_delete_failure.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/RolesController.php');
$start = strpos($source, 'public function delete(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

class FakeModel {
  public $id, $deleteResult = true;
  function exists($id = null) { return true; }
  function delete() { return $this->deleteResult; }
}
class FakeRequest { function allowMethod() { return true; } }

eval('class RolesHarness { public $request, $Role, $vars;
  function set($v) { $this->vars = $v; } '.$method.' }');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function run($deleteResult) {
  global $user;
  $user = null;
  $h = new RolesHarness();
  $h->request = new FakeRequest();
  $h->Role = new FakeModel();
  $h->Role->deleteResult = $deleteResult;
  try {
    $h->delete(5);
  } catch (Exception $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

list($r, $h) = run(true);
check('a delete that works answers the message', array($r, $h->vars['message'] ?? null), array('ok', 'The role has been deleted.'));
list($r, $h) = run(false);
check('a failed delete is an InternalErrorException, not a 200', $r, 'InternalErrorException');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
