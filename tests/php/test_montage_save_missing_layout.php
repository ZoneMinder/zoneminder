<?php
/*
 * Saving the montage layout with an empty Name saves over the selected layout.
 * If that layout no longer exists (deleted from another tab), the Save action
 * must refuse instead of inserting a new layout with an empty name.
 *
 * Runs the real action file against stubs.
 *
 * Run: php tests/php/test_montage_save_missing_layout.php [root]
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
        $this->name = $GLOBALS["existing_layouts"][(int)$id];
        $this->uid = 1;
      }
    }
    public static function find_one($p = array()) { return null; }
    public function Id() { return $this->id; }
    public function Name($n = null) { if ($n !== null) $this->name = $n; return $this->name; }
    public function UserId($u = null) { if ($u !== null) $this->uid = $u; return $this->uid; }
    public function Positions($p = null) { return $p; }
    public function save() { $GLOBALS["saved_layouts"][] = $this->name; $this->id = $this->id ?: 99; return true; }
  }
}');
class FakeUser { public function Id() { return 1; } }
function canEdit($x) { return false; }
function validCardinal($i) { return is_scalar($i) ? preg_replace('/\D/', '', $i) : ''; }
function zm_session_start() { return true; }
function zm_setcookie($name, $value, $options = array()) { $GLOBALS['setcookies'][] = array($name, $value); return true; }

function run_save($request) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/montage.php');
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $GLOBALS['warnings'] = $GLOBALS['saved_layouts'] = $GLOBALS['setcookies'] = array();
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

$existing_layouts = array(20 => 'Mine');

$base = array('object' => 'MontageLayout', 'Name' => '', 'Positions' => '{}');

$ex = run_save($base + array('zmMontageLayout' => '20'));
check('empty name saves over the selected existing layout', $ex === null && $saved_layouts === array('Mine'));

$ex = run_save($base + array('zmMontageLayout' => '77'));
check('empty name with a deleted layout id saves nothing', $saved_layouts === array());
check('empty name with a deleted layout id does not select a new layout', $setcookies === array());

echo "$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
