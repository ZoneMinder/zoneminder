<?php
// UsersController::delete() (API) could not delete a user who has User_Preferences rows (the
// web UI writes them for tag ordering). User_Preferences.UserId references Users.Id with no
// ON DELETE action, so deleting the user hit the foreign key and the API answered 500 with
// the user kept. The web UI (includes/actions/options.php) deletes the preferences first.
//
// UsersController can't be loaded without CakePHP, so delete() is taken from the source and
// run against stub models that run their SQL on sqlite with foreign keys on, using the
// User_Preferences definition from db/User_Preferences.sql.
// Run as: php tests/php/test_api_user_delete_preferences.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/UsersController.php');
$start = strpos($source, 'public function delete(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class MethodNotAllowedException extends Exception {}
function __($s) { return $s; }

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('CREATE TABLE Users (Id INTEGER PRIMARY KEY, Username TEXT)');
$pdo->exec('CREATE TABLE User_Preferences (Id INTEGER PRIMARY KEY, UserId INTEGER NOT NULL,
  Name TEXT NOT NULL, Value TEXT, FOREIGN KEY (UserId) REFERENCES Users(Id))');

class FakeUser {
  public $id;
  function exists() {
    global $pdo;
    $q = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE Id=?');
    $q->execute(array($this->id));
    return $q->fetchColumn() > 0;
  }
  function delete() {
    global $pdo;
    return $pdo->prepare('DELETE FROM Users WHERE Id=?')->execute(array($this->id));
  }
}
class FakeUserPreference {
  function deleteAll($conditions, $cascade = true) {
    global $pdo;
    $where = array();
    foreach ($conditions as $field => $value) {
      $where[] = preg_replace('/^UserPreference\./', '', $field).'='.$pdo->quote($value);
    }
    return $pdo->exec('DELETE FROM User_Preferences WHERE '.implode(' AND ', $where)) !== false;
  }
}
class FakeRequest {
  function allowMethod() { return true; }
}

eval('class UsersHarness { public $request, $User, $UserPreference, $vars;
  function set($v) { $this->vars = $v; }
  function loadModel($name) { if ($name == "UserPreference") $this->UserPreference = new FakeUserPreference(); }
  '.$method.' }');

function count_rows($sql) {
  global $pdo;
  return intval($pdo->query($sql)->fetchColumn());
}

$pdo->exec("INSERT INTO Users (Id, Username) VALUES (1, 'admin'), (5, 'viewer'), (6, 'other')");
$pdo->exec("INSERT INTO User_Preferences (UserId, Name, Value) VALUES (5, 'TagRecent', '[3,1]'), (6, 'TagRecent', '[2]')");

$user = null; // authentication off
$h = new UsersHarness();
$h->request = new FakeRequest();
$h->User = new FakeUser();
try {
  $h->delete(5);
  $result = $h->vars['message'];
} catch (Exception $e) {
  $result = get_class($e).': '.$e->getMessage();
}
check('a user with preferences is deleted', $result, 'The user has been deleted.');
check('the user row is gone', count_rows('SELECT COUNT(*) FROM Users WHERE Id=5'), 0);
check('their preferences are gone', count_rows('SELECT COUNT(*) FROM User_Preferences WHERE UserId=5'), 0);
check('other users keep their preferences', count_rows('SELECT COUNT(*) FROM User_Preferences WHERE UserId=6'), 1);

$h = new UsersHarness();
$h->request = new FakeRequest();
$h->User = new FakeUser();
$h->delete(1);
check('a user without preferences is still deleted', $h->vars['message'], 'The user has been deleted.');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
