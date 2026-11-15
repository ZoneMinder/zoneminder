<?php
// monitorprobe.php must tolerate Ffmpeg monitors whose Path has no host, and
// onvifprobe.php must say so when nothing was detected.
//
// Run as: php tests/php/test_probe_views.php

namespace ZM {
  function Debug($s) {}
  function urlForLog($s) { return $s; }
  function Error($s) {}
  function Warning($s) {}
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "  ok $name\n"; return; }
    $failures++;
    echo "  FAIL $name\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
  }
  function translate($s) { return $s; }
  function get_subnets($i) { return array(); }
  function get_arp_results() { return array(); }
  function dbFetchAll($sql) {
    if (strpos($sql, "'Ffmpeg'") === false) return array();
    return array(
      array('Id'=>1, 'Name'=>'no path', 'Path'=>''),
      array('Id'=>2, 'Name'=>'file', 'Path'=>'/var/lib/video.mp4'),
      array('Id'=>3, 'Name'=>'camera', 'Path'=>'rtsp://10.0.0.5/stream'),
    );
  }

  $dir = __DIR__.'/../../web/skins/classic/views/';
  $mp = file_get_contents($dir.'monitorprobe.php');
  if (!preg_match('/^function probeNetwork\(\).*?^\} # end function probeNetwork\(\)/ms', $mp, $m)) {
    echo "FAIL - probeNetwork() not found\n";
    exit(1);
  }
  eval($m[0]);

  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
  $_REQUEST = array();
  $result = probeNetwork();
  restore_error_handler();
  check('Ffmpeg monitors without a host raise no warnings', $warnings, array());
  check('probeNetwork still returns', $result, array());

  $op = file_get_contents($dir.'onvifprobe.php');
  foreach (array('cameras', 'profiles') as $v) {
    if (!preg_match('/^  if \(count\(\$'.$v.'\) <= \d\)\n    \$'.$v.'\[0\] = [^\n]*;/m', $op, $m)) {
      echo "FAIL - $v placeholder code not found\n";
      $failures++;
      continue;
    }
    $$v = array(0 => 'Choose');
    eval($m[0]);
    check("no detected $v shows the not-detected text", $$v[0], $v == 'cameras' ? 'NoDetectedCameras' : 'NoDetectedProfiles');
    $$v = array(0 => 'Choose', 'abc' => 'one');
    eval($m[0]);
    check("one detected entry keeps the choose text for $v", $$v[0], 'Choose');
  }
  exit($failures ? 1 : 0);
}
