<?php
//
// Regression test for GHSA-5w6w-qp25-2qqh.
//
// Changing a user's password (self-service or administrator-initiated) must
// revoke that user's existing JWT API tokens. ZoneMinder already has the
// mechanism: validateToken() rejects any token whose issued-at is earlier than
// the user's TokenMinExpiry, and the options page's revoke action sets
// TokenMinExpiry to time(). Before the fix the password-change paths rewrote
// only the password hash, so a pre-change access token kept working and a
// pre-change refresh token kept minting new access tokens for up to its 24h
// lifetime after the password was changed.
//
// The fix sets TokenMinExpiry to time() in the same step that rewrites the
// hash, in every genuine password-change path:
//   * the REST API (web/api/app/Model/User.php::beforeSave), covered
//     behaviourally below;
//   * the classic web UI (web/includes/actions/user.php), covered structurally;
//   * and, on master/1.38, the API self-edit whitelist must admit the field.
//
// The login-time legacy-hash upgrade (auth.php) rewrites the hash of the SAME
// password and must NOT revoke tokens; it is deliberately untouched and not
// asserted here.
//
// Run: php tests/php/test_password_change_revokes_tokens.php
//
error_reporting(E_ALL & ~E_DEPRECATED);

$failures = 0;
$passes = 0;
function check($name, $cond) {
  global $failures, $passes;
  if ($cond) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "FAIL - $name\n"; }
}

$root = dirname(__FILE__).'/../..';

// ---------------------------------------------------------------------------
// (1) Behavioural: the REST API User model revokes tokens on a password change.
// Include the real model with CakePHP's base stubbed so beforeSave() runs
// without a database. beforeSave is byte-identical on master, 1.38 and 1.36.
// ---------------------------------------------------------------------------
if (!class_exists('App')) {
  class App { public static function uses($a, $b) {} }
}
if (!class_exists('AppModel')) {
  class AppModel { public $data = array(); public $alias = 'User'; }
}

$model_file = $root.'/web/api/app/Model/User.php';
check('found the API User model', is_file($model_file));
require_once($model_file);
check('User model class loaded', class_exists('User'));

$before = time();
$model = new User();
$model->data = array('User' => array('Username' => 'bob', 'Password' => 'plaintextsecret'));
$model->beforeSave();
$after = time();

check('API: a changed password is bcrypt-hashed',
  isset($model->data['User']['Password']) &&
  $model->data['User']['Password'] !== 'plaintextsecret' &&
  password_verify('plaintextsecret', $model->data['User']['Password']));
check('API: a password change sets TokenMinExpiry',
  isset($model->data['User']['TokenMinExpiry']));
check('API: TokenMinExpiry is the change time (revokes earlier tokens)',
  isset($model->data['User']['TokenMinExpiry']) &&
  $model->data['User']['TokenMinExpiry'] >= $before &&
  $model->data['User']['TokenMinExpiry'] <= $after);

// A pre-change token (issued before the change) is now rejected; a token the
// user obtains after logging in again (issued after the change) is accepted.
// This mirrors validateToken(): revoked when issuedAt < TokenMinExpiry.
$minExpiry = isset($model->data['User']['TokenMinExpiry']) ? $model->data['User']['TokenMinExpiry'] : 0;
$preChangeIat = $before - 3600;   // issued an hour before the change
$postChangeIat = $after + 1;      // issued after the change
check('API: a pre-change token would be rejected (iat < TokenMinExpiry)',
  $preChangeIat < $minExpiry);
check('API: a post-change token would be accepted (iat >= TokenMinExpiry)',
  !($postChangeIat < $minExpiry));

// An edit that does NOT change the password must not revoke tokens.
$model2 = new User();
$model2->data = array('User' => array('Username' => 'bob', 'Language' => 'en_gb'));
$model2->beforeSave();
check('API: a non-password edit does not touch TokenMinExpiry',
  !isset($model2->data['User']['TokenMinExpiry']));

$model3 = new User();
$model3->data = array('User' => array('Username' => 'bob', 'Password' => ''));
$model3->beforeSave();
check('API: an empty password is ignored and does not revoke tokens',
  !isset($model3->data['User']['TokenMinExpiry']));

// ---------------------------------------------------------------------------
// (2) Structural: the classic web UI user action revokes tokens on a password
// change, in every save path (administrator edit and self edit).
// ---------------------------------------------------------------------------
$action_file = $root.'/web/includes/actions/user.php';
check('found the classic user action', is_file($action_file));
$src = file_get_contents($action_file);
// Drop comment-only lines so an explanatory comment can't satisfy a check.
$code = implode("\n", array_filter(explode("\n", $src),
  function($l) { return !preg_match('/^\s*(#|\/\/)/', $l); }));

$save_sites = preg_match_all('/\$changes\s*=\s*\$dbUser->changes\s*\(/', $code);
check('classic: found the save paths that build $changes', $save_sites >= 2);

$guard_sites = preg_match_all(
  '/if\s*\(\s*isset\s*\(\s*\$changes\[\s*[\'"]Password[\'"]\s*\]\s*\)\s*\)/', $code);
check('classic: every save path revokes tokens when the password changes',
  $guard_sites >= $save_sites && $guard_sites >= 2);

$assign_sites = preg_match_all(
  '/\$changes\[\s*[\'"]TokenMinExpiry[\'"]\s*\]\s*=\s*time\s*\(\s*\)/', $code);
check('classic: TokenMinExpiry is set to time() in each path',
  $assign_sites >= $save_sites && $assign_sites >= 2);

// ---------------------------------------------------------------------------
// (3) Structural (master/1.38 only): the API self-edit whitelist admits
// TokenMinExpiry so the model's beforeSave value is persisted. 1.36's API
// edit() saves with no field whitelist, so there is nothing to admit there.
// ---------------------------------------------------------------------------
$ctrl_file = $root.'/web/api/app/Controller/UsersController.php';
check('found the API UsersController', is_file($ctrl_file));
$ctrl = file_get_contents($ctrl_file);
if (strpos($ctrl, 'selfEditData') !== false && strpos($ctrl, '$fieldList = array_keys(') !== false) {
  $cc = implode("\n", array_filter(explode("\n", $ctrl),
    function($l) { return !preg_match('/^\s*(#|\/\/)/', $l); }));
  check('API controller: self-edit whitelist admits TokenMinExpiry on a password change',
    (bool)preg_match('/in_array\s*\(\s*[\'"]Password[\'"]\s*,\s*\$fieldList\s*\)/', $cc) &&
    (bool)preg_match('/\$fieldList\[\]\s*=\s*[\'"]TokenMinExpiry[\'"]/', $cc));
} else {
  check('API controller: no self-edit field whitelist on this branch (nothing to admit)', true);
}

echo '1..'.($passes + $failures)."\n";
if ($failures) {
  echo "# $failures of ".($passes + $failures)." checks failed\n";
  exit(1);
}
echo "# all $passes checks passed\n";
exit(0);
