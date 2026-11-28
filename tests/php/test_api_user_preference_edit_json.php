<?php
// UserPreferenceController::edit() set no response variables of its own: compact('user_preference') named a
// variable the method never defines, a PHP 8 warning on every edit, and the JSON view then printed the
// text of View/UserPreference/json/edit.ctp ("echo json_encode($user_preference);") instead of JSON.
// An edit now answers {"message": "Saved"}, or a 400 with the validation errors, as add() and the other
// controllers' edits do.
// The controller can't be loaded without CakePHP, so edit() is taken from the source and run in a stand-in.
// Run as: php tests/php/test_api_user_preference_edit_json.php [root]   (root defaults to the repo)
error_reporting(E_ALL);
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
foreach (array('BadRequestException', 'NotFoundException') as $c) if (!class_exists($c)) eval("class $c extends Exception {}");
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  function __construct($data) { $this->data = $data; }
  function is($t) { return true; }
  function allowMethod() {}
}
class FakeModel {
  public $ok = true;
  public $validationErrors = array();
  function exists($id) { return true; }
  function save($d) { return $this->ok; }
}
class Resp { public $status = 200; function statusCode($c) { $this->status = $c; } }

$source = file_get_contents("$root/web/api/app/Controller/UserPreferenceController.php");
$start = strpos($source, 'public function edit(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$code = substr($source, $start, $i - $start + 1);
eval('class Ctl { public $request; public $UserPreference; public $response; public $vars = array();
  function set($v) { $this->vars = $v; }
  function assertOwned($id) {}
  '.$code.' }');

function run($ok, $errors = array()) {
  $c = new Ctl; $c->request = new FakeRequest(array('Value' => 'v'));
  $c->UserPreference = new FakeModel; $c->UserPreference->ok = $ok; $c->UserPreference->validationErrors = $errors;
  $c->response = new Resp;
  $warned = false;
  set_error_handler(function() use (&$warned) { $warned = true; return true; });
  $c->edit(7);
  restore_error_handler();
  return array($c->response->status, $c->vars, $warned);
}

list($status, $vars, $warned) = run(true);
check('a saved edit answers 200', $status, 200);
check('a saved edit answers a Saved message', $vars, array('message' => 'Saved', '_serialize' => array('message')));
check('a saved edit raises no warning', $warned, false);
list($status, $vars, $warned) = run(false, array('Value' => array('too long')));
check('a refused edit answers 400', $status, 400);
check('a refused edit answers the validation errors', $vars, array('message' => array('Value' => array('too long')), '_serialize' => array('message')));
list($status, $vars) = run(false);
check('a refused edit without errors answers Error', $vars['message'], 'Error');
exit($failures ? 1 : 0);
