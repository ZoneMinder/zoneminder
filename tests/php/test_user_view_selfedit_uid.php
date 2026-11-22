<?php
// Non-admin with self edit enabled opens ?view=user with no uid: no undefined index notice.
// Run: php tests/php/test_user_view_selfedit_uid.php
define('ZM_USER_SELF_EDIT', 1);
function canEdit($a) { return false; }
class StubUser { function Id() { return 3; } }
set_error_handler(function($no, $str) { throw new ErrorException($str); });
$user = new StubUser();
$view = 'user';
$_REQUEST = array();
$notice = null;
try { include __DIR__.'/../../web/skins/classic/views/user.php'; }
catch (ErrorException $e) { $notice = $e->getMessage(); }
$ok = ($notice === null) && ($view === 'error');
echo ($ok ? "  ok" : "  FAIL")." no notice, redirected to error view (".var_export($notice, true).", $view)\n";
echo $ok ? "1 passed, 0 failed\n" : "0 passed, 1 failed\n";
exit($ok ? 0 : 1);
