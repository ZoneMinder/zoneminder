<?php
// includes/actions/montage.php treated a whitespace-only layout Name as a real name (it only
// compared against ''), saving a layout that shows as blank in the montage layout dropdown, and
// stored ' Mine ' with its padding while the duplicate check used the same untrimmed value.
// Runs the real action file with stubbed classes.
//
// Run as: php tests/php/test_montage_layout_name_trim.php [repo root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  class MontageLayout {
    public $id = 0;
    function __construct($id = 0) { $this->id = $id; }
    static function find_one($w, $o = array()) {
      if (isset($w['Name'])) $GLOBALS['lookups'][] = $w['Name'][0];
      return null;
    }
    function Name($n = null) { if ($n !== null) $GLOBALS['saved_name'] = $n; return 'x'; }
    function UserId($u = null) { return 1; }
    function Positions($p = null) { return ''; }
    function Id() { return $this->id ?: 9; }
    function save() { $GLOBALS['saved'] = true; return true; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $stubdir = sys_get_temp_dir().'/zm_montage_trim_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/MontageLayout.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canEdit($area, $mid = false) { return true; }
  function validCardinal($v) { return (int)$v; }
  function zm_session_start() {}
  function zm_setcookie($a, $b, $c = array()) {}
  $user = new class { function Id() { return 1; } };

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function runSave($name) {
    global $root, $error_message, $redirect, $action, $user;
    $GLOBALS['saved_name'] = null; $GLOBALS['saved'] = false; $GLOBALS['lookups'] = array();
    $error_message = ''; $redirect = '';
    $_REQUEST = array('object' => 'MontageLayout', 'Name' => $name, 'Positions' => '{}', 'zmMontageLayout' => '5');
    $action = 'Save';
    include $root.'/web/includes/actions/montage.php';
    return array($GLOBALS['saved_name'], $GLOBALS['saved'], $error_message !== '');
  }

  check('whitespace-only name is rejected', runSave("   "), array(null, false, true));
  check('name is trimmed before save', runSave("  Mine "), array('Mine', true, false));
  check('name is trimmed before the duplicate lookup', $GLOBALS['lookups'], array('Mine', 'Mine'));
  check('blank name still saves over the selected layout', runSave(''), array(null, true, false));

  @unlink($stubdir.'/includes/MontageLayout.php');
  @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
