<?php
// RolesController edit and UserPreferenceController add/edit saved only for POST
// (or PUT) but answered any other method with 200 and an empty or failure payload,
// where the other controllers refuse it with allowMethod (#8098, #7886). They now
// call allowMethod first, so a GET is refused before anything runs. (Roles add
// already answers a GET with 400.)
//
// The controllers can't be loaded without CakePHP, so each action is taken from
// the source and run against a stub request.
// Run as: php tests/php/test_api_role_userpref_methods.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
class MethodNotAllowedException extends Exception {}
// Thrown by the actions' own checks once past the method; with no method check
// a GET reaches them.
class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
function __($s) { return $s; }
class Reached extends Exception {}

// Any use of the controller beyond the request means the action got past the
// method check.
class FakeRequest {
  private $method;
  public $data = array();
  function __construct($m) { $this->method = $m; }
  function is($type) { return in_array($this->method, (array)$type); }
  function allowMethod() {
    if (!in_array($this->method, func_get_args())) throw new MethodNotAllowedException();
    throw new Reached();
  }
  function __get($n) { throw new Reached(); }
  function __call($n, $a) { throw new Reached(); }
}

function extract_method($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  if ($start === false) return null;
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

foreach (array('Roles', 'UserPreference') as $n => $controller) {
  $src = file_get_contents("$root/web/api/app/Controller/{$controller}Controller.php");
  $methods = extract_method($src, 'add')."\n".extract_method($src, 'edit');
  $class = "Fake{$controller}";
  eval("class $class { public \$request;
    function __get(\$n) { throw new Reached(); }
    function __call(\$n, \$a) { throw new Reached(); }
    $methods }");
  $actions = array('edit' => array('get', 'delete'));
  if ($controller == 'UserPreference') $actions = array('add' => array('get', 'put', 'delete')) + $actions;
  foreach ($actions as $action => $refused) {
    foreach ($refused as $m) {
      $c = new $class(); $c->request = new FakeRequest($m);
      try { $c->$action(1); $got = 'answered'; }
      catch (MethodNotAllowedException $e) { $got = 'refused'; }
      catch (Reached $e) { $got = "ran"; }
      catch (Exception $e) { $got = "answered"; }
      check("$controller $action refuses ".strtoupper($m), $got, 'refused');
    }
    $ok = ($action == 'add') ? 'post' : 'put';
    $c = new $class(); $c->request = new FakeRequest($ok);
    try { $c->$action(1); $got = 'answered'; }
    catch (MethodNotAllowedException $e) { $got = 'refused'; }
    catch (Reached $e) { $got = 'ran'; }
    check("$controller $action accepts ".strtoupper($ok), $got, 'ran');
  }
}

echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
exit($failures ? 1 : 0);
