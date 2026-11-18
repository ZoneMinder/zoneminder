<?php
// Cloning a monitor with ?dupId copies its zones to the new monitor when
// cloneZones is posted (#2906), instead of only adding the default "All" zone.
// php tests/php/test_monitor_clone_zones.php

namespace ZM {
  function Error($s) {}
  class Zone {
    static $saved = array();
    function save($v) { self::$saved[] = $v; return true; }
    function get_last_error() { return ''; }
  }
}
namespace {
  function validCardinal($v) { return (int)$v; }
  function canView($a, $m = false) { return true; }
  function dbFetchAll($sql, $col, $args) {
    return array(array('Id'=>5, 'MonitorId'=>$args[0], 'Name'=>'Door', 'Coords'=>'0,0 1,1'));
  }

  $src = file_get_contents(__DIR__.'/../../web/includes/actions/monitor.php');
  $a = strpos($src, '$dupId = ');
  $b = strpos($src, '// Zone coords', $a);
  if ($a === false || $b === false) { echo "FAIL - clone zones code not found\n"; exit(1); }
  $code = substr($src, $a, $b - $a);

  $mid = 9; $error_message = '';
  $_REQUEST = array('dupId'=>'3', 'cloneZones'=>'1');
  eval($code);
  $z = ZM\Zone::$saved;
  $ok = count($z) == 1 && $z[0]['MonitorId'] === 9 && $z[0]['Name'] === 'Door' && !isset($z[0]['Id']);
  echo ($ok ? "ok" : "FAIL")." - zones copied to the new monitor\n";

  ZM\Zone::$saved = array();
  $_REQUEST = array('dupId'=>'3');
  eval($code);
  $ok2 = count(ZM\Zone::$saved) == 0;
  echo ($ok2 ? "ok" : "FAIL")." - no copy without cloneZones\n";
  exit($ok && $ok2 ? 0 : 1);
}
