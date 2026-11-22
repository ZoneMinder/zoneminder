<?php
// Saving a preset from a new zone, whose OverloadFrames and ExtendAlarmFrames
// inputs are blank, sent NULL for those NOT NULL columns and the save failed.
// The save_preset branch is taken from web/includes/actions/zone.php and run
// against stubs.
//
//   php tests/php/test_zone_save_preset_blank_frames.php [root]

namespace ZM {
  class ZonePreset {
    public static $saved = null;
    public static function getConfigurableFields() {
      return array('MinPixelThreshold', 'MinAlarmPixels', 'MinBlobs', 'OverloadFrames', 'ExtendAlarmFrames');
    }
    public static function isBuiltInName($n) { return false; }
    public static function find_one($p) { return null; }
    public function save($d) { self::$saved = $d; return true; }
    public function Id() { return 8; }
    public function toArray() { return array(); }
  }
  function AuditAction() {}
}
namespace {
  function translate($s) { return $s; }
  function ajaxError($m) { throw new \Exception($m); }
  function ajaxResponse($d) { return $d; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $source = file_get_contents($root.'/web/includes/actions/zone.php');
  if (!preg_match('/^  if \( \$action == \'save_preset\' \) \{\n(.*?)\n  \} else if \( \$action == \'delete_preset\' \)/ms', $source, $m)) {
    echo "not ok - save_preset not found in zone.php\n";
    exit(1);
  }
  $action = 'save_preset';
  $_REQUEST = array('presetName' => 'mine', 'newZone' => array(
    'MinPixelThreshold' => '25', 'MinAlarmPixels' => '0,5', 'MinBlobs' => '',
    'OverloadFrames' => '', 'ExtendAlarmFrames' => ''));
  eval($m[1]);
  $d = \ZM\ZonePreset::$saved;

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  check('blank OverloadFrames saves 0', $d['OverloadFrames'], 0);
  check('blank ExtendAlarmFrames saves 0', $d['ExtendAlarmFrames'], 0);
  check('blank nullable MinBlobs stays NULL', $d['MinBlobs'], null);
  check('percent value formatted', $d['MinAlarmPixels'], '0.50');
  exit($failures ? 1 : 0);
}
