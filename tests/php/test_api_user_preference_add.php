<?php
// UserPreference::add() saved the body as given: an Id in it picked another user's row, and a
// nested UserPreference[UserId] was saved instead of the caller's id.
// Run as: php tests/php/test_api_user_preference_add.php [root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$source = file_get_contents("$root/web/api/app/Controller/UserPreferenceController.php");
$start = strpos($source, 'public function add(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);
class BadRequestException extends Exception {}
function __($s) { return $s; }
eval('namespace ZM; function Warning($m) {} function Error($m) {}');
class StubUser {
  function Id() { return 5; }
  function System() { return 'None'; }
}
class FakeRequest {
  public $data;
  function __construct($d) { $this->data = $d; }
  function is($t) { return true; }
  function input($f, $a) { return null; }
}
class FakeHandler { function requestedWith($t) { return false; } }
// Like Cake's Model::save(): an Id in the data picks the row; a nested alias array wins.
class FakeModel {
  public $id = null, $primaryKey = 'Id', $validationErrors = array(), $written = null, $existing = null;
  function find($type, $o = array()) { return $this->existing; }
  function create() { $this->id = null; }
  function save($d) {
    $row = (isset($d['UserPreference']) && is_array($d['UserPreference'])) ? $d['UserPreference'] : $d;
    $this->written = array('id' => isset($row['Id']) ? $row['Id'] : $this->id,
      'UserId' => isset($row['UserId']) ? $row['UserId'] : null);
    return true;
  }
}
eval('class PCtl { public $request, $UserPreference, $RequestHandler;
  function set($v) {}
  function currentUserId() { global $user; return $user ? $user->Id() : null; }
  '.$method.' }');
function add($data, $existing = null) {
  global $user;
  $user = new StubUser();
  $c = new PCtl();
  $c->request = new FakeRequest($data);
  $c->RequestHandler = new FakeHandler();
  $c->UserPreference = new FakeModel();
  $c->UserPreference->existing = $existing;
  $c->add();
  return $c->UserPreference->written;
}
$w = add(array('Id' => 8, 'Name' => 'n', 'Value' => 'v'));
check('an Id in the body does not pick the row', $w['id'] === 8, false);
$w = add(array('UserPreference' => array('UserId' => 9, 'Name' => 'n', 'Value' => 'v'), 'Name' => 'n'));
check('a nested UserId is replaced by the caller', $w['UserId'], 5);
$w = add(array('Name' => 'n', 'Value' => 'v', 'UserId' => 9));
check('a flat UserId is replaced by the caller', $w['UserId'], 5);
$w = add(array('Id' => 8, 'Name' => 'n'), array('UserPreference' => array('Id' => 3)));
check('the caller\'s existing row is the one updated', $w['id'], 3);
$w = add(array('UserPreference' => array('UserPreference' => array('Id' => 1, 'UserId' => 1, 'Value' => 'x'), 'Name' => 'n')));
check('a body nested twice is saved as the caller, not its inner Id/UserId', array($w['UserId'], $w['id']), array(5, null));
exit($failures ? 1 : 0);
