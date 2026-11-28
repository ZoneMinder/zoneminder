<?php
// UserPreferenceController::edit() read the preference from the request body. A body such as
// {"UserPreference":"x"} left a string where it then set $data['Id'], an Error, and the API answered a 500.
// A UserPreference that is not an object is a 400 now; an object, or a body without the UserPreference key,
// is saved to the row in the URL as before.
// The controller can't be loaded without CakePHP, so edit() is taken from the source and run in a stand-in.
// Run as: php tests/php/test_api_user_preference_edit_nonobject.php [root]   (root defaults to the repo)
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
  public $saved = null;
  function exists($id) { return true; }
  function save($d) { $this->saved = $d; return true; }
}
class Resp { function statusCode($c) {} }

$source = file_get_contents("$root/web/api/app/Controller/UserPreferenceController.php");
$start = strpos($source, 'public function edit(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$code = substr($source, $start, $i - $start + 1);
eval('class Ctl { public $request; public $UserPreference; public $response; public $vars;
  function set($v) { $this->vars = $v; }
  function assertOwned($id) {}
  '.$code.' }');

// What a call does: the exception class and message, or "saved" with the data that reached save().
function run($data) {
  $c = new Ctl; $c->request = new FakeRequest($data);
  $c->UserPreference = new FakeModel; $c->response = new Resp;
  try {
    @$c->edit(7);
  } catch (Throwable $e) {
    return get_class($e).': '.$e->getMessage();
  }
  return $c->UserPreference->saved;
}

check('a string UserPreference is a bad request', run(array('UserPreference' => 'x')), 'BadRequestException: UserPreference must be an object');
check('a number UserPreference is a bad request', run(array('UserPreference' => 5)), 'BadRequestException: UserPreference must be an object');
check('an object UserPreference is saved to the URL row', run(array('UserPreference' => array('Value' => 'v', 'Id' => 99))), array('Value' => 'v', 'Id' => 7));
check('a body without the UserPreference key is saved to the URL row', run(array('Value' => 'v')), array('Value' => 'v', 'Id' => 7));
check('an empty UserPreference object is saved to the URL row', run(array('UserPreference' => array())), array('Id' => 7));
exit($failures ? 1 : 0);
