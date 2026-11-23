<?php
// UsersController::delete() (API) removed the user's User_Preferences rows and only then
// deleted the user. When the user delete failed (e.g. the foreign key from a concurrent
// insert), the preferences were already gone and the API answered 500 with the user kept but
// its settings lost. delete() now runs both statements in one transaction, so a failed user
// delete rolls the preference deletion back.
//
// UsersController can't be loaded without CakePHP, so delete() is taken from the source and
// run against stubs: an in-memory User_Preferences table plus a datasource whose
// begin/commit/rollback records each call and restores the rows deleted since begin on
// rollback (a deleteAll with no begin in between is permanent, as in the original code).
// Run as: php tests/php/test_api_user_delete_keeps_preferences.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/UsersController.php');
$start = strpos($source, 'public function delete(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

$users = array();
$prefs = array();
$calls = array();
$snapshot = null;

class FakeDataSource {
  function begin() {
    global $calls, $prefs, $snapshot;
    $calls[] = 'begin';
    $snapshot = $prefs;
    return true;
  }
  function commit() {
    global $calls, $snapshot;
    $calls[] = 'commit';
    $snapshot = null;
    return true;
  }
  function rollback() {
    global $calls, $prefs, $snapshot;
    $calls[] = 'rollback';
    if ($snapshot !== null) $prefs = $snapshot;
    $snapshot = null;
    return true;
  }
}

class FakeUser {
  public $id;
  public $deleteResult = true;
  function exists() { global $users; return array_key_exists($this->id, $users); }
  function getDataSource() { global $ds; return $ds; }
  function delete() {
    global $users;
    if (!$this->deleteResult) return false;
    unset($users[$this->id]);
    return true;
  }
}

class FakeUserPreference {
  function deleteAll($conditions, $cascade = true) {
    global $prefs;
    $userId = $conditions['UserPreference.UserId'];
    foreach ($prefs as $key => $row) {
      if ($row['UserId'] == $userId) unset($prefs[$key]);
    }
    return true;
  }
}

class FakeRequest { function allowMethod() { return true; } }

eval('class UsersHarness { public $request, $User, $UserPreference, $vars;
  function set($v) { $this->vars = $v; }
  function loadModel($m) { if ($m == "UserPreference") $this->UserPreference = new FakeUserPreference(); }
  '.$method.' }');

$ds = new FakeDataSource();

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Users 5 (the one being deleted) and 6 (bystander); two preference rows for 5, one for 6.
function reset_rows() {
  global $users, $prefs, $calls, $snapshot;
  $users = array(5 => 'viewer', 6 => 'other');
  $prefs = array(
    10 => array('Id' => 10, 'UserId' => 5, 'Name' => 'TagRecent', 'Value' => '[3,1]'),
    11 => array('Id' => 11, 'UserId' => 5, 'Name' => 'Language', 'Value' => 'en_gb'),
    12 => array('Id' => 12, 'UserId' => 6, 'Name' => 'TagRecent', 'Value' => '[2]'),
  );
  $calls = array();
  $snapshot = null;
}

function run_delete($id, $deleteResult) {
  global $user;
  $user = null; // authentication off, so the self-delete guard does not fire
  $h = new UsersHarness();
  $h->request = new FakeRequest();
  $h->User = new FakeUser();
  $h->User->deleteResult = $deleteResult;
  try {
    $h->delete($id);
    return array('ok', $h, null);
  } catch (Exception $e) {
    return array(get_class($e), $h, $e->getMessage());
  }
}

function prefs_for($userId) {
  global $prefs;
  $ids = array();
  foreach ($prefs as $key => $row) if ($row['UserId'] == $userId) $ids[] = $key;
  sort($ids);
  return $ids;
}

// (1) A delete that works: the user and their preferences go, nobody else's do.
reset_rows();
list($result, $h) = run_delete(5, true);
check('a working delete is not an exception', $result, 'ok');
check('a working delete answers the message', isset($h->vars['message']) ? $h->vars['message'] : null, 'The user has been deleted.');
check('the user row is gone', array_key_exists(5, $users), false);
check('their preference rows are gone', prefs_for(5), array());
check('another user keeps their preferences', prefs_for(6), array(12));

// (2) A delete that fails: the exception is the same, and the preference rows are put back.
reset_rows();
list($result, $h, $message) = run_delete(5, false);
check('a failed delete throws InternalErrorException', $result, 'InternalErrorException');
check('a failed delete keeps the same message', $message, 'The user could not be deleted.');
check('the user row is still there', array_key_exists(5, $users), true);
check('the preference rows are still there', prefs_for(5), array(10, 11));
check('another user still keeps their preferences', prefs_for(6), array(12));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
