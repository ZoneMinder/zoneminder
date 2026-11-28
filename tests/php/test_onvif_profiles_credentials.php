<?php
// probeProfiles() in onvifprobe.php adds the camera user name and password to
// the stream URI the camera returned. The URI is chosen by the camera, so the
// credentials must only go into one for the host that was probed, over a
// scheme that uses them. Usage: php test_onvif_profiles_credentials.php [root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/skins/classic/views/onvifprobe.php');
$failures = 0;
function check($what, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $what\n"; return; }
  echo "FAIL - $what: got ".json_encode($got).", want ".json_encode($want)."\n";
  $failures++;
}
if (!function_exists('ZM\\Debug')) { eval('namespace ZM; function Debug() {}'); }
if (!function_exists('execONVIF')) {
  function execONVIF($cmd, $log_cmd = null) { return $GLOBALS['lines']; }
}
if (!preg_match('/^function probeProfiles\(.*?^\} \/\/ end function probeProfiles/ms', $src, $m)) { echo "FAIL - probeProfiles() not found\n"; exit(1); }
eval($m[0]);

function pathFor($uri) {
  $GLOBALS['lines'] = array("tok1, Main, H264, 1920, 1080, 25, RTP-Unicast, $uri");
  $r = probeProfiles('http://192.168.1.50:80/onvif/device_service', '1', 'admin', 'p@ss/w');
  return isset($r[0]) ? $r[0]['Path'] : null;
}

check('same host gets the credentials, encoded twice',
  pathFor('rtsp://192.168.1.50:554/s'), 'rtsp://admin:p%2540ss%252Fw@192.168.1.50:554/s');
check('another host gets no credentials', pathFor('rtsp://attacker.example/s'), 'rtsp://attacker.example/s');
check('a file URI gets no credentials', pathFor('file:///etc/passwd'), 'file:///etc/passwd');
check('a host that only starts like the probed one gets no credentials',
  pathFor('rtsp://192.168.1.50.attacker.example/s'), 'rtsp://192.168.1.50.attacker.example/s');
exit($failures ? 1 : 0);
