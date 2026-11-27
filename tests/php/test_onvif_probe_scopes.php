<?php
// probeCameras() must URL decode the scope values of a WS-Discovery reply
// (onvif://www.onvif.org/name/Front%20Door arrives as name='Front%20Door').
//
// Run as: php tests/php/test_onvif_probe_scopes.php

namespace ZM {
  function Debug($s) {}
  function Error($s) {}
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "  ok $name\n"; return; }
    $failures++;
    echo "  FAIL $name\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
  }
  function execONVIF($cmd, $log_cmd = null) {
    return array("http://10.0.0.5/onvif/device_service, 1.2, (name='Front%20Door', hardware='AXIS%20M1065-L', location/city='Sao%20Paulo')");
  }

  $op = file_get_contents(__DIR__.'/../../web/skins/classic/views/onvifprobe.php');
  if (!preg_match('/^function probeCameras\(.*?^\} \/\/ end function probeCameras/ms', $op, $m)) {
    echo "FAIL - probeCameras() not found\n";
    exit(1);
  }
  eval($m[0]);
  $c = probeCameras('');
  check('one camera parsed', count($c), 1);
  check('name is decoded', $c[0]['monitor']['Name'], 'Front Door');
  check('model is decoded', $c[0]['model'], 'AXIS M1065-L');
  check('location note is decoded', $c[0]['monitor']['Notes'], "location/city=Sao Paulo\n");
  exit($failures ? 1 : 0);
}
