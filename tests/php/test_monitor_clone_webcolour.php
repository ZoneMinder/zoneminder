<?php
// Cloning a monitor kept the source's WebColour, so the clone and the source were drawn in
// the same colour. A new monitor gets a random one; the clone must too.
// Run: php tests/php/test_monitor_clone_webcolour.php <repo root>
namespace ZM {
  class Monitor {
    private $d = ['Id'=>3, 'Name'=>'Front', 'WebColour'=>'#112233'];
    function __construct($id = 0) {}
    function __call($f, $a) { if ($a) $this->d[$f] = $a[0]; return $this->d[$f] ?? null; }
    function GroupIds() { return []; }
  }
}
namespace {
  function validCardinal($v) { return (int)$v; }
  function random_colour() { return '#abcdef'; }
  define('ZM_OPT_X10', 0);
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
  if (!preg_match('/^if \(isset\(\$_REQUEST\[\'dupId\'\]\)\) \{\n(.*?)^\}\n/ms', $src, $m)) {
    echo "[FAIL] dupId block not found\n";
    exit(1);
  }
  $_REQUEST = ['dupId'=>3];
  $monitors_by_name = [];
  $mid = null;
  eval($m[1]);
  $ok = ($monitor->WebColour() !== '#112233') && $monitor->Id() === null;
  echo $ok ? "[PASS] clone gets its own WebColour (".$monitor->WebColour().")\n" : "[FAIL] clone kept WebColour ".$monitor->WebColour()."\n";
  exit($ok ? 0 : 1);
}
