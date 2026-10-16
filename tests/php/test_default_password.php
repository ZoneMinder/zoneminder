<?php
// Tests the default-password helpers in web/includes/auth.php.
//
// db/zm_create.sql seeds the admin account with the password the user guide
// documents, so a login with it proves nothing. validateUser() refuses it as a
// credential (the API and user=/pass= get ZM_DEFAULT_PASSWORD_REFUSED), and the
// web login asks for a new password, which newPasswordProblem() checks before
// replaceDefaultPassword() stores it.
//
// Run: php tests/php/test_default_password.php
//
// Including auth.php needs a database (User.php pulls in database.php), so the
// pure functions and their constants are lifted out of the file, as in
// test_auth_request_string.php.
$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
if ($auth_src === false) {
  echo "FAIL could not read auth.php\n";
  exit(1);
}
foreach (array(
  "/^define\('ZM_DEFAULT_PASSWORD',.*?;$/m",
  "/^define\('ZM_DEFAULT_PASSWORD_REFUSED',.*?;$/m",
  '/^function isDefaultPassword\(.*?^}$/ms',
  '/^function passwordProblem\(.*?^}$/ms',
  '/^function passwordProblemText\(.*?^}$/ms',
  '/^function passwordMinLength\(.*?^}$/ms',
  '/^function newPasswordProblem\(.*?^}$/ms',
  '/^function requestString\(.*?^}$/ms',
  '/^function isDefaultPasswordChangePost\(.*?^}$/ms',
) as $pattern) {
  if (!preg_match($pattern, $auth_src, $matches)) {
    echo "FAIL $pattern not found in auth.php\n";
    exit(1);
  }
  eval($matches[0]);
}

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok   $name\n";
  } else {
    $failures++;
    echo "FAIL $name: expected ".var_export($expected, true).', got '.var_export($got, true)."\n";
  }
}

// The seeded hash in db/zm_create.sql.in must be the documented default, or
// none of this protects anything.
$create_src = file_get_contents(__DIR__.'/../../db/zm_create.sql.in');
preg_match("/INSERT INTO `Users`.*?VALUES \(\s*'admin',\s*'([^']+)'/s", $create_src, $m);
check('zm_create.sql seeds admin with the default password',
  isset($m[1]) && password_verify(ZM_DEFAULT_PASSWORD, $m[1]), true);

echo "isDefaultPassword\n";
check('the default password', isDefaultPassword('admin'), true);
check('another password', isDefaultPassword('s3cret'), false);
check('case differs', isDefaultPassword('Admin'), false);
check('trailing space', isDefaultPassword('admin '), false);
check('empty', isDefaultPassword(''), false);
check('null (parameter not sent)', isDefaultPassword(null), false);
check('array (?password[]=admin)', isDefaultPassword(array('admin')), false);

echo "passwordProblem (user page, API, login forms)\n";
check('an ordinary password', passwordProblem('n3w-pass'), null);
check('admin', passwordProblem('admin'), 'NewPasswordIsDefault');
check('Admin (case differs)', passwordProblem('Admin'), null);
check('empty', passwordProblem(''), 'NewPasswordRequired');
check('array (user[Password][]=x)', passwordProblem(array('x')), 'NewPasswordRequired');
check('API text without a language file', passwordProblemText('NewPasswordIsDefault'), 'The new password must not be admin.');
check('API text, unknown key', passwordProblemText('Other'), 'Other');

echo "ZM_PASSWORD_MIN_LENGTH\n";
check('option missing (upgrade without zmupdate -f): no minimum', passwordMinLength(), 0);
check('option missing: a short password is fine', passwordProblem('abc'), null);
define('ZM_PASSWORD_MIN_LENGTH', '8');
check('minimum 8', passwordMinLength(), 8);
check('7 characters', passwordProblem('abcdefg'), 'PasswordTooShort');
check('8 characters', passwordProblem('abcdefgh'), null);
check('8 characters, multibyte', passwordProblem('ééééèèèè'), null);
check('admin is still the reason for admin', passwordProblem('admin'), 'NewPasswordIsDefault');
check('API text has the number', passwordProblemText('PasswordTooShort'), 'The new password must have at least 8 characters.');
check('form: too short after a matching confirmation', newPasswordProblem('short', 'short'), 'PasswordTooShort');

echo "newPasswordProblem\n";
check('a new password that matches its confirmation', newPasswordProblem('n3w-pass', 'n3w-pass'), null);
check('no new password', newPasswordProblem(null, null), 'NewPasswordRequired');
check('empty new password', newPasswordProblem('', ''), 'NewPasswordRequired');
check('confirmation differs', newPasswordProblem('n3w-pass', 'n3w-pas'), 'PasswordsDifferent');
check('no confirmation', newPasswordProblem('n3w-pass', null), 'PasswordsDifferent');
check('the default again', newPasswordProblem('admin', 'admin'), 'NewPasswordIsDefault');

echo "isDefaultPasswordChangePost (lets the change form skip reCAPTCHA)\n";
$_SESSION = array();
$_REQUEST = array();
check('no form shown, nothing posted', isDefaultPasswordChangePost(), false);
$_SESSION['passwordChangeNonce'] = 'abc123';
check('form shown, plain login posted', isDefaultPasswordChangePost(), false);
$_REQUEST['password_change_nonce'] = 'abc123';
check('form shown, its nonce posted', isDefaultPasswordChangePost(), true);
$_REQUEST['password_change_nonce'] = 'abc124';
check('form shown, another nonce posted', isDefaultPasswordChangePost(), false);
$_REQUEST['password_change_nonce'] = array('abc123');
check('nonce posted as an array', isDefaultPasswordChangePost(), false);
unset($_SESSION['passwordChangeNonce']);
$_REQUEST['password_change_nonce'] = 'abc123';
check('nonce posted, none in the session', isDefaultPasswordChangePost(), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
