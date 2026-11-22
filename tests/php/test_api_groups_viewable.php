<?php
// The API showed every group to every user. GroupsController::view() and the group
// listings now drop the groups none of whose monitors the user may view, as the web
// UI does (ZM\Group::canView).
//
// GroupsController can't be loaded without CakePHP, so the methods are taken from the
// source and run against stubs.
// Run as: php tests/php/test_api_groups_viewable.php <repo root>

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = realpath($argv[1] ?? __DIR__.'/../..');
$source = file_get_contents($root.'/web/api/app/Controller/GroupsController.php');
if ($source === false) {
  echo "FAIL - could not read GroupsController.php under $root\n";
  exit(1);
}

// Take a method out of the source by name, matching braces.
function extract_method($source, $name) {
  $start = strpos($source, 'function '.$name.'(');
  if ($start === false) return null;
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$methods = '';
foreach (array('canViewGroup', 'viewableGroups', 'view') as $name) {
  $method = extract_method($source, $name);
  if ($method !== null) $methods .= $method."\n";
}
$hasViewable = extract_method($source, 'viewableGroups') !== null;

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
function __($s) { return $s; }

// Id => bool, whether the user may view the group.
$visible = array();
eval('namespace ZM; class Group {
  private $id;
  function __construct($id) { $this->id = $id; }
  function canView($u) { global $visible; return !empty($visible[$this->id]); }
}');

// Groups table: Id => exists (a truthy placeholder).
$groups = array();
class FakeModel {
  public $primaryKey = 'Id';
  public $recursive;
  function exists($id) { global $groups; return array_key_exists($id, $groups); }
  function find($type, $options) {
    $id = $options['conditions']['Group.Id'];
    return array('Group' => array('Id' => $id, 'Name' => 'g'.$id));
  }
}
eval('class FakeController { public $Group; public $out;
  function set($a) { $this->out = $a; }
  '.($hasViewable ? 'function runViewableGroups($g) { return $this->viewableGroups($g); }' : '').'
  '.$methods.' }');

function run_view($id) {
  $c = new FakeController();
  $c->Group = new FakeModel();
  try { $c->view($id); }
  catch (Exception $e) { return $e; }
  return $c;
}

$user = new stdClass();
$visible = array(5 => false, 6 => true, 7 => false);
$groups = array(5 => true, 6 => true, 7 => true);

// A restricted user asking for a group they may not view is told it does not exist.
$r = run_view(5);
check('view of a group the user cannot view is not found',
  $r instanceof Exception ? get_class($r) : 'no exception', 'NotFoundException');

// A group the user may view comes back.
$r = run_view(6);
check('view of a group the user can view returns it',
  $r instanceof Exception ? get_class($r) : $r->out['group']['Group']['Id'], 6);

// With auth off there is no per-user filtering.
$user = null;
$r = run_view(5);
check('with auth off any group is returned',
  $r instanceof Exception ? get_class($r) : $r->out['group']['Group']['Id'], 5);

// Listing keeps only the groups the user may view.
$user = new stdClass();
if ($hasViewable) {
  $c = new FakeController();
  $c->Group = new FakeModel();
  $input = array(
    array('Group' => array('Id' => 5)),
    array('Group' => array('Id' => 6)),
    array('Group' => array('Id' => 7)),
  );
  $ids = array_map(function($g) { return $g['Group']['Id']; }, $c->runViewableGroups($input));
  check('viewableGroups returns only the groups the user can view', $ids, array(6));
} else {
  check('viewableGroups exists', false, true);
}

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
