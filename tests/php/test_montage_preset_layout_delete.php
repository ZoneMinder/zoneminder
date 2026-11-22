<?php
/*
 * The montage layout Delete action must refuse to delete the built-in preset
 * layouts (Auto, Freeform, N Wide) instead of removing them, and still delete
 * ordinary user layouts.
 *
 * Runs the real action file against stubs.
 *
 * Run: php tests/php/test_montage_preset_layout_delete.php [root]
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
    public $id;
    public function __construct($id = null) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function Name() { return $GLOBALS["layout_names"][(int)$this->id] ?? ""; }
    public function UserId() { return 1; }
    public function delete() { $GLOBALS["deleted_layouts"][] = $this->id; return true; }
  }
}');
function canEdit($x) { return true; }
function validCardinal($i) { return is_scalar($i) ? preg_replace('/\D/', '', $i) : ''; }
function zm_session_start() { return true; }
function zm_setcookie($name, $value, $options = array()) { $GLOBALS['setcookies'][] = array($name, $value); return true; }

// Runs the montage action file for one Delete request; returns PHP warnings/exceptions.
function run_delete($request) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/montage.php');
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $GLOBALS['warnings'] = $GLOBALS['deleted_layouts'] = $GLOBALS['setcookies'] = array();
  $_SESSION = array('zmMontageLayout' => $request['zmMontageLayout']);
  $_COOKIE = array('zmMontageLayout' => $request['zmMontageLayout']);
  $problems = array();
  set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
  $_REQUEST = $request;
  $fn = function ($action) use ($src) {
    global $error_message, $redirect;
    $error_message = ''; $redirect = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  $ex = $fn('Delete');
  restore_error_handler();
  if ($ex) $problems[] = $ex;
  return $problems;
}

$GLOBALS['layout_names'] = array(1 => 'Auto', 2 => '4 Wide', 3 => 'My layout');

echo "montage layout delete action\n";

$p = run_delete(array('object' => 'MontageLayout', 'zmMontageLayout' => '1'));
check('Auto delete raises nothing', count($p) == 0);
check('Auto is not deleted', $GLOBALS['deleted_layouts'] === array());
check('Auto delete records a warning', count($GLOBALS['warnings']) > 0);

$p = run_delete(array('object' => 'MontageLayout', 'zmMontageLayout' => '2'));
check('4 Wide delete raises nothing', count($p) == 0);
check('4 Wide is not deleted', $GLOBALS['deleted_layouts'] === array());
check('4 Wide delete records a warning', count($GLOBALS['warnings']) > 0);

$p = run_delete(array('object' => 'MontageLayout', 'zmMontageLayout' => '3'));
check('My layout delete raises nothing', count($p) == 0);
check('My layout is deleted', $GLOBALS['deleted_layouts'] === array('3'));
check('My layout delete records no warning', $GLOBALS['warnings'] === array());

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
