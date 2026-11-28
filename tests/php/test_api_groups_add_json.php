<?php
// POST /api/groups answered with an HTML flash redirect instead of JSON, so a client got no
// new group id back, and any non-POST request fell through to the missing
// View/Groups/json/add.ctp (500). add() now refuses non-POST and always sets a JSON body.
//
// GroupsController can't be loaded without CakePHP, so add() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_groups_add_json.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
if (!preg_match('#^/#', $root)) $root = getcwd().'/'.$root;
$file = $root.'/web/api/app/Controller/GroupsController.php';
if (!is_file($file)) {
  fwrite(STDERR, "no such controller: $file\n");
  exit(1);
}

$source = file_get_contents($file);
$start = strpos($source, 'public function add(');
if ($start === false) {
  fwrite(STDERR, "no add() in $file\n");
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }
if (!function_exists('ZM\Error')) { eval('namespace ZM; function Error($s) {}'); }

class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return strtolower($type) == strtolower($this->method); }
}

class FakeMonitor {
  function find($type, $options = array()) { return array(); }
}

class FakeGroup {
  public $id = null;
  public $alias = 'Group';
  public $primaryKey = 'Id';
  public $validationErrors = array();
  public $Monitor;
  public $saveResult = true;
  public $saveCalls = 0;
  function __construct() { $this->Monitor = new FakeMonitor(); }
  function create() {}
  function saveAssociated($data, $options = array()) {
    $this->saveCalls++;
    if ($this->saveResult) $this->id = 31;
    return $this->saveResult;
  }
}

eval('class GroupsHarness { public $Group, $request, $response, $vars = array(), $flashed = array();
  function set($v) { $this->vars = array_merge($this->vars, (array)$v); }
  function flash($message, $url = null) { $this->flashed[] = array($message, $url); return "FLASH"; }
  private function requestField($alias, $field) { return null; }
  private function requestAssociatedIds($alias, $assoc) { return array(); }
  private function requireMembershipEdit($ids) {}
  private function pinRequestId($model, $id) { $model->id = $id; }
  '.$method.' }');

function run_add($method, $save_result, $data = array()) {
  global $user;
  $user = null;
  $h = new GroupsHarness();
  $h->request = new FakeRequest($method, $data); $h->response = new FakeResponse;
  $h->Group = new FakeGroup();
  $h->Group->saveResult = $save_result;
  try {
    $h->add();
  } catch (Throwable $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

list($r, $h) = run_add('POST', true, array('Group' => array('Name' => 'Front')));
check('POST save answers ok', $r, 'ok');
check('POST save answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('POST save returns the new id', isset($h->vars['id']) ? $h->vars['id'] : null, 31);
check('POST save serializes message and id', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));
check('POST save does not flash a redirect', $h->flashed, array());

list($r, $h) = run_add('POST', false, array('Group' => array('Name' => 'Front')));
check('rejected save answers ok', $r, 'ok');
check('rejected save returns no id', array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', null);
check('rejected save serializes message and id', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));

list($r, $h) = run_add('GET', true);
check('GET is a BadRequestException', $r, 'BadRequestException');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
