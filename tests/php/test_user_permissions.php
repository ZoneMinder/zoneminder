<?php
// Security regression test: ZM\User must answer area permission checks itself.
//
// Filter::canView()/canEdit()/canDelete() ask the user object $u->canEdit('System'),
// $u->canView('Events') and so on. ZM\User had no such methods, so the calls fell through
// to ZM_Object::__call(), which stored the area name as a property and returned it. The
// non-empty string is truthy, so every user passed as a System editor: an Events=View
// account could save and run a filter with AutoExecute (an OS command). refs GHSA-ff93-w6fm-vqxr
//
// Unlike test_filter_canedit_autoexecute.php this drives the real ZM\User and ZM\User_Role
// classes, so the permission methods under test are the ones the web UI calls.
//
// Run as: php tests/php/test_user_permissions.php

namespace ZM;

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

// User.php, User_Role.php and Filter.php require these; none of the methods under test touch
// the database or the other models, so empty stubs stand in for them.
$stubdir = sys_get_temp_dir().'/zm_user_permissions_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php', 'Group.php', 'Group_Permission.php',
               'Monitor_Permission.php', 'User_Preference.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }

require_once __DIR__.'/../../web/includes/User.php';
require_once __DIR__.'/../../web/includes/User_Role.php';
require_once __DIR__.'/../../web/includes/Filter.php';

function make_user($id, array $perms, $role = null) {
  $u = new User(array_merge(array('Id' => $id, 'Username' => 'u'.$id), $perms));
  if ($role) $u->Role($role);
  return $u;
}

$nobody = make_user(10, array());
$eventsViewer = make_user(11, array('Events' => 'View'));
$eventsEditor = make_user(12, array('Events' => 'Edit'));
$systemViewer = make_user(13, array('System' => 'View'));
$systemEditor = make_user(14, array('System' => 'Edit'));
$monitorsCreator = make_user(15, array('Monitors' => 'Create'));
$roleSystemEditor = make_user(16, array(), new User_Role(array('Id' => 1, 'Name' => 'admins', 'System' => 'Edit')));
$roleEventsViewer = make_user(17, array(), new User_Role(array('Id' => 2, 'Name' => 'viewers', 'Events' => 'View')));

// ---- the User methods themselves -------------------------------------------

check('Events=View user is not a System editor', $eventsViewer->canEdit('System'), false);
check('Events=View user is not a System viewer', $eventsViewer->canView('System'), false);
check('Events=View user can view Events', $eventsViewer->canView('Events'), true);
check('Events=View user cannot edit Events', $eventsViewer->canEdit('Events'), false);
check('Events=Edit user can view Events', $eventsEditor->canView('Events'), true);
check('Events=Edit user can edit Events', $eventsEditor->canEdit('Events'), true);
check('System=View user can view System', $systemViewer->canView('System'), true);
check('System=View user cannot edit System', $systemViewer->canEdit('System'), false);
check('System=Edit user can edit System', $systemEditor->canEdit('System'), true);
check('Monitors=Create user can edit Monitors', $monitorsCreator->canEdit('Monitors'), true);
check('Monitors=Create user can create Monitors', $monitorsCreator->canCreate('Monitors'), true);
check('Monitors=Create user cannot create Events', $monitorsCreator->canCreate('Events'), false);
check('user with no permissions cannot view Events', $nobody->canView('Events'), false);

// A role grants what the user's own fields don't.
check('role System=Edit makes the user a System editor', $roleSystemEditor->canEdit('System'), true);
check('role Events=View lets the user view Events', $roleEventsViewer->canView('Events'), true);
check('role Events=View does not let the user edit Events', $roleEventsViewer->canEdit('Events'), false);

// Asking must not change the user: __call() used to store the area name as a property.
$probe = make_user(18, array('System' => 'None'));
$probe->canEdit('System');
check('asking about System leaves System unchanged', $probe->System(), 'None');

// Only permission areas answer; anything else is refused rather than read as a field.
check('a non-permission field is not an area', $systemEditor->canView('Username'), false);
check('a non-permission field is not an area for edit', $systemEditor->canEdit('Password'), false);

// ---- Filter decisions with real users --------------------------------------

function make_filter($ownerId, array $flags) {
  $f = new Filter();
  $f->UserId($ownerId);
  foreach ($flags as $k => $v) $f->$k($v);
  return $f;
}

$f = make_filter(11, array('AutoExecute' => 1, 'AutoExecuteCmd' => 'true'));
check('Events=View owner cannot edit an AutoExecute filter', $f->canEdit($eventsViewer), false);
$f = make_filter(12, array('AutoExecute' => 1, 'AutoExecuteCmd' => 'true'));
check('Events=Edit owner cannot edit an AutoExecute filter', $f->canEdit($eventsEditor), false);
$f = make_filter(99, array('AutoExecute' => 1, 'AutoExecuteCmd' => 'true'));
check('System editor can edit an AutoExecute filter', $f->canEdit($systemEditor), true);
check('role System editor can edit an AutoExecute filter', $f->canEdit($roleSystemEditor), true);

$f = make_filter(99, array());
check('Events=Edit user cannot edit a filter owned by another user', $f->canEdit($eventsEditor), false);
check('Events=View user cannot view a saved filter owned by another user', (function () use ($f, $eventsViewer) {
  $f->Id(5);
  return $f->canView($eventsViewer);
})(), false);
check('System viewer can view a filter owned by another user', $f->canView($systemViewer), true);
check('Events=Edit user cannot delete a filter owned by another user', $f->canDelete($eventsEditor), false);
check('System editor can delete a filter owned by another user', $f->canDelete($systemEditor), true);

$f = make_filter(11, array());
check('Events=View owner can edit a plain query filter', $f->canEdit($eventsViewer), true);
$f = make_filter(11, array('AutoDelete' => 1));
check('Events=View owner cannot edit an AutoDelete filter', $f->canEdit($eventsViewer), false);
$f = make_filter(12, array('AutoDelete' => 1));
check('Events=Edit owner can edit an AutoDelete filter', $f->canEdit($eventsEditor), true);

array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
