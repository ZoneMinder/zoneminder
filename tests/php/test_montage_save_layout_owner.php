<?php
/*
 * Saving a montage layout keeps its owner. An admin (System edit) may save
 * over another user's layout, but that must not hand the layout to the admin:
 * the owner could then no longer edit it. A new layout belongs to the user
 * who saves it.
 *
 * Runs the real action file against stubs.
 *
 * Run: php tests/php/test_montage_save_layout_owner.php [root]
 */
$root = $argv[1] ?? (__DIR__.'/../..');
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}

eval('namespace ZM {
  function Warning($m) { $GLOBALS["warnings"][] = $m; }
  function Error($m) { $GLOBALS["warnings"][] = $m; }
  function Info($m) {} function Debug($m) {}
  class MontageLayout {
    public $id = null; public $name = ""; public $uid = 0;
    public function __construct($id = null) {
      if ($id && isset($GLOBALS["existing_layouts"][(int)$id])) {
        $this->id = (int)$id;
        list($this->name, $this->uid) = $GLOBALS["existing_layouts"][(int)$id];
      }
    }
    public static function find_one($p = array()) { return null; }
    public function Id() { return $this->id; }
    public function Name($n = null) { if ($n !== null) $this->name = $n; return $this->name; }
    public function UserId($u = null) { if ($u !== null) $this->uid = $u; return $this->uid; }
    public function Positions($p = null) { return $p; }
    public function save() { $GLOBALS["saved"][] = array($this->name, $this->uid); $this->id = $this->id ?: 99; return true; }
  }
}');
class FakeUser { public function Id() { return $GLOBALS['user_id']; } }
function canEdit($x) { return $GLOBALS['is_admin']; }
function validCardinal($i) { return is_scalar($i) ? preg_replace('/\D/', '', $i) : ''; }
function zm_session_start() { return true; }
function zm_setcookie($name, $value, $options = array()) { return true; }

function run_save($request, $user_id, $is_admin) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/montage.php');
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $GLOBALS['warnings'] = $GLOBALS['saved'] = array();
  $GLOBALS['user_id'] = $user_id;
  $GLOBALS['is_admin'] = $is_admin;
  $_SESSION = array();
  $_REQUEST = $request;
  $fn = function ($action) use ($src) {
    global $error_message, $redirect, $user;
    $user = new FakeUser();
    $error_message = ''; $redirect = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  return $fn('Save');
}

$existing_layouts = array(20 => array('Bob', 5));
$base = array('object' => 'MontageLayout', 'Positions' => '{}');

$ex = run_save($base + array('Name' => '', 'zmMontageLayout' => '20'), 1, true);
check('admin saving another user\'s layout saves it', $ex === null && count($saved) === 1);
check('admin saving another user\'s layout keeps its owner', $saved === array(array('Bob', 5)));

$ex = run_save($base + array('Name' => '', 'zmMontageLayout' => '20'), 5, false);
check('owner saving their layout keeps it theirs', $ex === null && $saved === array(array('Bob', 5)));

$ex = run_save($base + array('Name' => 'New'), 1, true);
check('a new layout belongs to the user saving it', $ex === null && $saved === array(array('New', 1)));

echo "$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
