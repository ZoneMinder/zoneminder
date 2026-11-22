<?php
// Notifications::edit() and UserPreference::edit() checked ownership of the Id in the URL
// but saved the body as given, so a body naming another Id overwrote that row instead.
// Run as: php tests/php/test_api_edit_ignores_body_id.php [root]
error_reporting(E_ALL & ~E_WARNING); // edit() has a stray compact() warning
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
foreach (array('NotFoundException', 'UnauthorizedException') as $c) if (!class_exists($c)) eval("class $c extends Exception {}");
if (!function_exists('__')) { function __($s) { return $s; } }
class StubUser {
  public $uid; public $sys;
  function __construct($uid, $sys) { $this->uid = $uid; $this->sys = $sys; }
  function Id() { return $this->uid; }
  function System() { return $this->sys; }
}
class FakeRequest {
  public $data;
  function __construct($data) { $this->data = $data; }
  function is($t) { return true; }
  function allowMethod() {}
}
class Resp { function statusCode($c) {} }
// Like Cake's Model::save(): an Id in the data decides which row is written.
class FakeModel {
  public $id = null; public $primaryKey = 'Id'; public $validationErrors = array();
  public $written = null; public $alias;
  function __construct($alias) { $this->alias = $alias; }
  function exists($id = null) { return true; }
  function create() { $this->id = null; }
  function find($type, $o = array()) { return array($this->alias => array('Id' => 7, 'UserId' => 5)); }
  function save($d) {
    $row = isset($d[$this->alias]) ? $d[$this->alias] : $d;
    $this->written = array('id' => isset($row['Id']) ? $row['Id'] : $this->id, 'UserId' => isset($row['UserId']) ? $row['UserId'] : null);
    return true;
  }
}

function method($file, $name) {
  global $root;
  $source = file_get_contents("$root/web/api/app/Controller/$file");
  $start = strpos($source, "public function $name(");
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}
eval('class NCtl { public $request; public $Notification; public $response; public $vars;
  function set($v) { $this->vars = $v; }
  function _isAdmin() { global $user; return $user->System() == "Edit"; }
  function _userId() { global $user; return $user->Id(); }
  '.method('NotificationsController.php', 'edit').'
  '.method('NotificationsController.php', 'add').' }');
eval('class PCtl { public $request; public $UserPreference;
  function set($v) {}
  function currentUserId() { global $user; return $user->Id(); }
  function assertOwned($id) {}
  '.method('UserPreferenceController.php', 'edit').' }');

// The caller (user 5) owns row 7 and names row 8, another user's, in the body.
$user = new StubUser(5, 'None');
$c = new NCtl; $c->request = new FakeRequest(array('Notification' => array('Id' => 8, 'Token' => 'x')));
$c->Notification = new FakeModel('Notification'); $c->response = new Resp;
$c->edit(7);
check('notifications edit writes the URL row, not the body Id', $c->Notification->written['id'], 7);

// add() with another user's Id in the body and no Token must create a new row.
$c = new NCtl; $c->request = new FakeRequest(array('Notification' => array('Id' => 8, 'PushState' => 'enabled')));
$c->Notification = new FakeModel('Notification'); $c->response = new Resp;
$c->add();
check('notifications add does not write the body Id', $c->Notification->written['id'], null);

$c = new PCtl; $c->request = new FakeRequest(array('Id' => 8, 'UserId' => 9, 'Name' => 'n', 'Value' => 'v'));
$c->UserPreference = new FakeModel('UserPreference');
$c->edit(7);
check('user preference edit writes the URL row, not the body Id', $c->UserPreference->written['id'], 7);
check('user preference edit does not hand the row to another user', $c->UserPreference->written['UserId'], 5);

$user = new StubUser(1, 'Edit');
$c = new PCtl; $c->request = new FakeRequest(array('Id' => 8, 'UserId' => 9, 'Name' => 'n'));
$c->UserPreference = new FakeModel('UserPreference');
$c->edit(7);
check('admin edit still writes the URL row', $c->UserPreference->written['id'], 7);
check('admin may set UserId', $c->UserPreference->written['UserId'], 9);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
