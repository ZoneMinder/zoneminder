<?php
// Re-registering an existing token without a UserId reassigned it to the caller.
// add() now keeps the stored owner unless an admin names one.
// Run as: php tests/php/test_api_notification_add_keeps_owner.php [root]
error_reporting(E_ALL);
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
foreach (array('BadRequestException','UnauthorizedException') as $c) if (!class_exists($c)) eval("class $c extends Exception {}");
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  function __construct($data) { $this->data = $data; }
  function is($t) { return $t == 'post'; }
}
class FakeModel {
  public $id = null; public $saved = null; public $validationErrors = array();
  public $row = array('Notification' => array('Id' => 7, 'UserId' => 5, 'Token' => 'T'));
  function create() { $this->id = null; }
  function find($type, $o = array()) { return $this->row; }
  function save($d) { $this->saved = $d; if (!$this->id) $this->id = 8; return true; }
}
class Resp { function statusCode($c) {} }

$source = file_get_contents("$root/web/api/app/Controller/NotificationsController.php");
$start = strpos($source, 'public function add(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$code = substr($source, $start, $i - $start + 1);
eval('class Ctl { public $request; public $Notification; public $response; public $vars;
  public $admin = true;
  function _isAdmin() { return $this->admin; }
  function _userId() { return 1; }
  function set($v) { $this->vars = $v; }
  '.str_replace(array('$this->_isAdmin()', '$this->_userId()'), array('$this->admin', '1'), $code).' }');

function run($admin, $data) {
  $c = new Ctl; $c->admin = $admin; $c->request = new FakeRequest($data);
  $c->Notification = new FakeModel; $c->response = new Resp;
  $c->add();
  return $c->Notification->saved['Notification'];
}
$r = run(true, array('Token' => 'T', 'MonitorList' => '1,2'));
check('admin re-register without UserId keeps owner', array_key_exists('UserId', $r), false);
$r = run(true, array('Token' => 'T', 'UserId' => 9));
check('admin naming UserId sets it', $r['UserId'], 9);
exit($failures ? 1 : 0);
