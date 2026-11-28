<?php
// Clearing the Full Name box on the user page is not saved: User::Name($new) skips
// the assignment when $new is '' (loose != null), so the old name is written back.
// Run: php tests/php/test_user_clear_full_name.php <repo root>
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

$stub_dir = sys_get_temp_dir().'/zm_user_stub_'.getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
$GLOBALS['queries'] = array();
function dbQuery($sql, $params = null) { $GLOBALS['queries'][] = array($sql, $params); return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
}
namespace ZM {
  function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {}
  // Copied from web/includes/functions.php; Object.php changes() calls it.
  function scalar_values_differ($old, $new) {
    if (is_array($old) or is_array($new)) {
      return $old != $new;
    }
    if (is_bool($old)) $old = (int)$old;
    if (is_bool($new)) $new = (int)$new;
    return (string)($old === null ? '' : $old) !== (string)($new === null ? '' : $new);
  }
}
STUB
);
foreach (array('Group_Permission', 'Monitor_Permission', 'User_Preference') as $f)
  file_put_contents($stub_dir.'/'.$f.'.php', "<?php\n");
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/User.php');

$row = array('Id' => 5, 'Username' => 'bob', 'Name' => 'Bob Smith', 'Email' => '', 'Phone' => '');
$u = new ZM\User($row);
$changes = $u->changes(array('Name' => ''));
check('clearing the name is seen as a change', isset($changes['Name']));
$GLOBALS['queries'] = array();
$u->save($changes);
$sql = $GLOBALS['queries'][0][0];
$params = $GLOBALS['queries'][0][1];
$fields = array();
preg_match_all('/`(\w+)`=\?/', $sql, $m);
$saved = array_combine($m[1], array_slice($params, 0, count($m[1])));
check('the empty name is written', $saved['Name'] === '', 'Name written: '.var_export($saved['Name'], true));

$u2 = new ZM\User($row);
$u2->save($u2->changes(array('Name' => 'Robert')));
check('a new name is still written', $u2->Name() === 'Robert');
$u3 = new ZM\User(array('Id' => 6, 'Username' => 'amy', 'Name' => ''));
check('an empty name still reads as the username', $u3->Name() === 'amy');

echo $failures ? "\n$failures failure(s)\n" : "\nall passed\n";
exit($failures ? 1 : 0);
