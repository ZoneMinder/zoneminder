<?php
// Saving a monitor with a new Model name must look the name up under the
// monitor's manufacturer, not across all manufacturers.
// Run as: php tests/php/test_monitor_new_model_manufacturer.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $ok ? '' : "\n        got: ".var_export($got, true)."\n       want: ".var_export($want, true));
}

eval('namespace ZM {
class Manufacturer { public $Id; public function Id() { return $this->Id; }
  public static function find_one($p) { return null; }
  public function save($d) { $this->Id = 99; return true; }
  public function get_last_error() { return ""; } }
class Model { public static $rows = array(); public static $next = 100; public $Id;
  public function Id() { return $this->Id; }
  public static function find_one($p) {
    foreach (self::$rows as $r) {
      $ok = true;
      foreach ($p as $k => $v) { if ((string)$r->$k !== (string)$v) $ok = false; }
      if ($ok) return $r;
    }
    return null;
  }
  public function save($d) { foreach ($d as $k => $v) $this->$k = $v; $this->Id = self::$next++; self::$rows[] = $this; return true; }
  public function get_last_error() { return ""; } }
}');

$src = file_get_contents($root.'/web/includes/actions/monitor.php');
$start = strpos($src, "if (!\$newMonitor['ManufacturerId'] and");
$end = strpos($src, '$monitor = new ZM\Monitor($mid);');
$block = substr($src, $start, $end - $start);

function run_block($block, $newMonitor) {
  $error_message = '';
  eval($block);
  return array($newMonitor, $error_message);
}

// Manufacturer 1 already has model "X100" (Id 5); manufacturer 2 does not.
$existing = new ZM\Model();
$existing->Id = 5; $existing->Name = 'X100'; $existing->ManufacturerId = 1;
ZM\Model::$rows[] = $existing;

list($m, $err) = run_block($block, array('ManufacturerId'=>'2', 'Manufacturer'=>'', 'ModelId'=>'', 'Model'=>'X100'));
check('same model name under another manufacturer is not reused', $m['ModelId'] != 5, true);
check('new model is created for the chosen manufacturer', ZM\Model::find_one(array('Name'=>'X100', 'ManufacturerId'=>'2')) !== null, true);

list($m, $err) = run_block($block, array('ManufacturerId'=>'1', 'Manufacturer'=>'', 'ModelId'=>'', 'Model'=>'X100'));
check('model under the same manufacturer is reused', $m['ModelId'], 5);

exit($failures ? 1 : 0);
