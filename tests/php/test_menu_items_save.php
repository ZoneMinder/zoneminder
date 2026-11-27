<?php
// Saving Options > Menu must leave entries that were not on the submitted form
// alone (they used to be disabled, because an absent checkbox reads as unchecked).
//
// The real skin function library and the real actions/options.php are loaded
// with the database layer stubbed.
//
// Run as: php tests/php/test_menu_items_save.php   (from the repo root)

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($s) { $GLOBALS['warnings'][] = $s; }

  class MenuItem {
    public static $items = array();
    public $d;
    public $saved = null;
    public function __construct($d = array()) { $this->d = $d + array('Link' => null, 'Label' => null, 'Icon' => null, 'IconType' => 'material', 'SortOrder' => 0, 'Enabled' => 1); }
    public static function find($p = array(), $o = array()) { return self::$items; }
    public function __call($n, $a) { return $this->d[$n] ?? null; }
    public function save($v) { $this->saved = $v; $this->d = $v + $this->d; return true; }
    public function get_last_error() { return ''; }
    public function effectiveIcon() { return 'menu'; }
    public function effectiveIconType() { return 'material'; }
    public function displayLabel() { return ($this->d['Label'] ?? '') !== '' ? $this->d['Label'] : $this->d['MenuKey']; }
  }
}

namespace {
  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) { $passes++; echo "  ok $name\n"; }
    else { $failures++; echo "  FAIL $name\n    got:  " . var_export($got, true) . "\n    want: " . var_export($want, true) . "\n"; }
  }

  $GLOBALS['queries'] = array();
  function canView($a) { return true; }
  function canEdit($a) { return true; }
  function translate($s) { return $s; }
  function validHtmlStr($i) { return $i === null ? '' : htmlspecialchars($i, ENT_QUOTES); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }
  function dbLastError() { return ''; }

  $skin = 'classic';
  $view = 'montage';
  require_once(__DIR__.'/../../web/skins/classic/includes/functions.php');

  // --- save and delete actions ---------------------------------------------
  function run_action($action) {
    global $error_message;
    $error_message = '';
    include(__DIR__.'/../../web/includes/actions/options.php');
  }
  set_include_path(sys_get_temp_dir().'/zmstub'.PATH_SEPARATOR.get_include_path());
  $stub = sys_get_temp_dir().'/zmstub/includes';
  @mkdir($stub, 0777, true);
  file_put_contents($stub.'/config_values.php', '<?php');
  file_put_contents($stub.'/MenuItem.php', '<?php');

  $a = new ZM\MenuItem(array('Id' => 1, 'MenuKey' => 'Console', 'Enabled' => 1));
  $b = new ZM\MenuItem(array('Id' => 2, 'MenuKey' => 'Wiki', 'Enabled' => 1));
  ZM\MenuItem::$items = array($a, $b);
  $_REQUEST = array('items' => array(1 => array('Id' => 1, 'Enabled' => '1', 'SortOrder' => '10')));
  run_action('menuitems');
  check('listed entry saved', $a->saved['Enabled'], 1);
  check('entry missing from the form is not rewritten', $b->saved, null);

  $c = new ZM\MenuItem(array('Id' => 3, 'MenuKey' => 'KeepKey', 'Link' => 'keep-link', 'Icon' => 'keep-icon', 'Enabled' => 1));
  ZM\MenuItem::$items = array($c);
  $_REQUEST = array('items' => array(3 => array('Label' => array('x'), 'Link' => array('y'), 'Icon' => array('z'), 'MenuKey' => array('k'))));
  $thrown = null;
  try {
    run_action('menuitems');
  } catch (Throwable $e) {
    $thrown = $e;
  }
  check('array-valued menu item does not throw TypeError', $thrown ? get_class($thrown).': '.$thrown->getMessage() : null, null);
  check('array-valued menu item keeps its Link', $c->saved['Link'] ?? null, 'keep-link');
  check('array-valued menu item keeps its Icon', $c->saved['Icon'] ?? null, 'keep-icon');
  check('array-valued menu item keeps its MenuKey', $c->saved['MenuKey'] ?? null, 'KeepKey');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
