<?php
// UsersController: ZM_USER_SELF_EDIT in the API.
//
// view() and edit() wrote `$canEdit = A or B;`, which assigns A and drops B, so a user could
// never view or edit their own record through the API with ZM_USER_SELF_EDIT on. beforeFilter()
// also turned away every user with System=None before view() or edit() ran.
//
// The fix lets a user without System=Edit change only what the web UI lets them change about
// themselves (includes/actions/user.php): Password, Language and HomeView. These checks cover
// the two helpers that decide that. UsersController can't be loaded without CakePHP, so the
// methods are taken from the source and run in a stand-in.
//
// Run as: php tests/php/test_api_user_self_edit.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/UsersController.php');

// The precedence bug itself: no `$canX = ... or ...` / `... and ...` assignments left.
check('no assignment combined with a low-precedence or/and',
  preg_match('/\$can\w+\s*=[^;]*\)\s+(or|and)\s+\(/', $source), 0);

$methods = '';
foreach (array('isSelfEdit', 'selfEditData') as $name) {
  if (!preg_match('/^  private function '.$name.'\(.*?^  }\n/ms', $source, $m)) {
    echo "FAIL - could not find $name in UsersController.php\n";
    exit(1);
  }
  $methods .= str_replace('private function', 'public function', $m[0]);
}

class BadRequestException extends Exception {}
function __($s) { return $s; }
class FakeUser {
  private $id;
  function __construct($id) { $this->id = $id; }
  function Id() { return $this->id; }
}
$langdir = sys_get_temp_dir().'/zm_self_edit_'.getmypid();
@mkdir($langdir.'/lang', 0700, true);
touch($langdir.'/lang/en_gb.php');
define('ZM_PATH_WEB', $langdir);
define('ZM_USER_SELF_EDIT', true);

eval('class SelfEditHarness { public $request; '.$methods.' }');

function selfEdit($action, $pass, $uid) {
  global $user;
  $user = $uid ? new FakeUser($uid) : null;
  $h = new SelfEditHarness();
  $h->request = (object)array('params' => array('action' => $action, 'pass' => $pass));
  return $h->isSelfEdit();
}

function data($in) {
  $h = new SelfEditHarness();
  try {
    return $h->selfEditData($in);
  } catch (BadRequestException $e) {
    return 'BadRequest';
  }
}

check('view of own id is self edit', selfEdit('view', array('2'), 2), true);
check('edit of own id is self edit', selfEdit('edit', array('2'), 2), true);
check('edit of another id is not', selfEdit('edit', array('1'), 2), false);
check('index is not', selfEdit('index', array(), 2), false);
check('delete of own id is not', selfEdit('delete', array('2'), 2), false);
check('add is not', selfEdit('add', array('2'), 2), false);
check('no id is not', selfEdit('edit', array(), 2), false);
check('no user is not', selfEdit('edit', array('2'), null), false);

check('only Password, Language and HomeView are kept',
  data(array('User' => array('Password' => 'p', 'Language' => 'en_gb', 'HomeView' => 'console',
    'System' => 'Edit', 'Monitors' => 'Edit', 'Id' => '1', 'Username' => 'admin', 'Enabled' => '1'))),
  array('User' => array('Password' => 'p', 'Language' => 'en_gb', 'HomeView' => 'console')));
check('bare fields are read the same way',
  data(array('HomeView' => 'console', 'System' => 'Edit', 'Id' => '1')),
  array('User' => array('HomeView' => 'console')));
check('bare fields next to a User entry are dropped',
  data(array('User' => array('HomeView' => 'console'), 'System' => 'Edit')),
  array('User' => array('HomeView' => 'console')));
check('an empty password is not saved', data(array('User' => array('Password' => '', 'HomeView' => 'x'))),
  array('User' => array('HomeView' => 'x')));
check('a language without a lang file is refused', data(array('User' => array('Language' => '../x'))), 'BadRequest');
check('a language given as an array is refused', data(array('User' => array('Language' => array('a')))), 'BadRequest');
check('an empty language is kept (clears it, as in the UI)', data(array('User' => array('Language' => ''))),
  array('User' => array('Language' => '')));
check('nothing allowed leaves nothing', data(array('User' => array('System' => 'Edit'))), array('User' => array()));
check('non-array data leaves nothing', data('junk'), array('User' => array()));

unlink($langdir.'/lang/en_gb.php');
rmdir($langdir.'/lang');
rmdir($langdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
