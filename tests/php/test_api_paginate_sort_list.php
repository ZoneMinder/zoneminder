<?php
// Cake's Paginator uses the request's sort as an array key and lowercases its direction, so /roles, /users,
// /logs and /events with a page or limit, called with sort[]=x or direction[]=x (or the named parameter
// sort[]:x), died with a TypeError, a 500. AppController::requireScalarSort() makes it a 400, and each
// paginating index() calls it before paginate().
//   php tests/php/test_api_paginate_sort_list.php [repo root]

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

class BadRequestException extends Exception {}

$controllers = $root.'/web/api/app/Controller/';
$app = file_get_contents($controllers.'AppController.php');
$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

$found = preg_match('/^  protected function requireScalarSort\(\) \{.*?^  \}\n/ms', $app, $m);
check('AppController::requireScalarSort() exists', $found, 1);
if ($found) {
  eval('class Harness { public $request; '.$m[0].' public function call() { try { $this->requireScalarSort(); } catch (BadRequestException $e) { return $e->getMessage(); } return "ok"; } }');
}

function verdict($named, $query) {
  $h = new Harness();
  $h->request = new stdClass();
  $h->request->params = array('named' => $named);
  $h->request->query = $query;
  return $h->call();
}

if ($found) {
  check('no sort is accepted', verdict(array(), array()), 'ok');
  check('a sort and direction are accepted', verdict(array(), array('sort' => 'Name', 'direction' => 'desc')), 'ok');
  check('a named sort and direction are accepted', verdict(array('sort' => 'Name', 'direction' => 'asc'), array()), 'ok');
  check('a list in the query sort is refused', verdict(array(), array('sort' => array('x'))), 'sort must be a single value');
  check('a nested array in the query sort is refused', verdict(array(), array('sort' => array('a' => array('b')))), 'sort must be a single value');
  check('an empty list in the query sort is refused', verdict(array(), array('sort' => array())), 'sort must be a single value');
  check('a list in the query direction is refused', verdict(array(), array('sort' => 'Id', 'direction' => array('x'))), 'direction must be a single value');
  check('a list in the named sort is refused', verdict(array('sort' => array('x')), array()), 'sort must be a single value');
  check('a list in the named direction is refused', verdict(array('direction' => array('x')), array()), 'direction must be a single value');

}

foreach (array('Roles', 'Users', 'Logs', 'Events') as $name) {
  $src = file_get_contents($controllers.$name.'Controller.php');
  $call = strpos($src, 'requireScalarSort()');
  $paginate = strpos($src, '->paginate(');
  check("$name calls requireScalarSort() before paginate()", $call !== false and $paginate !== false and $call < $paginate, true);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
