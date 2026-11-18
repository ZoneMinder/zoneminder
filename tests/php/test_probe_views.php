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

  // probeProfiles() puts the credentials into the stream URL: none when there
  // are none, and a username with reserved characters is encoded.
  if (!preg_match('/^function probeProfiles\(.*?^\} \/\/ end function probeProfiles/ms', $op, $m)) {
    echo "FAIL - probeProfiles() not found\n";
    exit(1);
  }
  function execONVIF($cmd, $log_cmd = null) {
    return array('Profile_1, main, H264, 1920, 1080, 25, RTP-Unicast, rtsp://10.0.0.5:554/stream1');
  }
  eval($m[0]);
  $r = probeProfiles('http://10.0.0.5/onvif/device_service', '1', '', '');
  check('no credentials leave the stream URL without userinfo', $r[0]['Path'], 'rtsp://10.0.0.5:554/stream1');
  $r = probeProfiles('http://10.0.0.5/onvif/device_service', '1', 'ad@min', 'pw');
  check('username is URL encoded twice like the password', $r[0]['Path'], 'rtsp://ad%2540min:pw@10.0.0.5:554/stream1');
  $r = probeProfiles('http://10.0.0.5/onvif/device_service', '1', 'admin', '');
  check('username alone keeps the colon', $r[0]['Path'], 'rtsp://admin:@10.0.0.5:554/stream1');
  // The probe is POSTed and monitor.php urldecode()s the whole Path once, so
  // the password must come out as a single encoded value that ffmpeg decodes
  // once. rawurlencode, because ffmpeg leaves a literal + alone.
  foreach (array('p@ss:w/rd#1%', 'a b', 'a+b', "\xC3\xA4") as $pw) {
    $r = probeProfiles('http://10.0.0.5/onvif/device_service', '1', 'admin', $pw);
    preg_match('|^rtsp://admin:([^@]*)@|', $r[0]['Path'], $pm);
    check('password '.bin2hex($pw).' is single encoded after the monitor view decodes Path', isset($pm[1]) ? urldecode($pm[1]) : null, rawurlencode($pw));
    check('password '.bin2hex($pw).' decodes back to itself after two decodes', isset($pm[1]) ? urldecode(urldecode($pm[1])) : null, $pw);
    check('password '.bin2hex($pw).' is not decoded to a plus', strpos(isset($pm[1]) ? urldecode($pm[1]) : '', '+') === false || strpos($pw, '+') !== false, true);
  }
  exit($failures ? 1 : 0);
}
