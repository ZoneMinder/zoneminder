<?php
// Usage: php test_navbar_storage_multiserver.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
  }
}

eval('namespace ZM {
  class Storage {
    public static $rows = array();
    public $sid; public $name;
    public function __construct($name, $sid) { $this->name = $name; $this->sid = $sid; }
    public static function find($f = null, $o = null) { return self::$rows; }
    public function ServerId() { return $this->sid; }
    public function Name() { return $this->name; }
    public function disk_usage_percent() { return 10; }
    public function disk_used_space() { return 1; }
    public function disk_total_space() { return 10; }
    public function event_disk_space() { return 1; }
  }
  function Debug($m) {}
}');

function canView($p) { return true; }
function translate($s) { return $s; }
function human_filesize($b) { return $b.'B'; }
function validHtmlStr($s) { return $s; }

$path = $root.'/web/skins/classic/includes/functions.php';
$src = file_get_contents($path);
if (!preg_match('/^function getStorageHTML\(.*?\n\}/ms', $src, $m)) {
  echo "Could not find getStorageHTML() in $path\n";
  exit(1);
}
eval($m[0]);

function names() {
  preg_match_all('/>([A-Za-z]+): \d+%/', getStorageHTML(), $mm);
  return $mm[1];
}

ZM\Storage::$rows = array(
  new ZM\Storage('Default', 0),
  new ZM\Storage('Unset', null),
  new ZM\Storage('Mine', 2),
  new ZM\Storage('Other', 3),
);

// ZM_SERVER_ID is a constant, so it can only be defined once; run the
// undefined (single server) case first.
check('single server install lists every area', names(), array('Default', 'Unset', 'Mine', 'Other'));

define('ZM_SERVER_ID', 2);
check('multi-server skips areas owned by another server', names(), array('Default', 'Unset', 'Mine'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
