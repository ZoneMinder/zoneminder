<?php
// probeProfiles() in onvifprobe.php: names with commas, duplicate stream types,
// and the Transport to Method mapping. Usage: php test_onvif_profiles.php <root>
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
if (!preg_match('/^    if \( [^\n]*\$profile\[\'Transport\'\].*?\$monitor\[\'Method\'\] = \'rtpRtsp\';\n    \}/ms', $src, $mm)) { echo "FAIL - Method mapping not found\n"; exit(1); }
$mapping = $mm[0];
function methodFor($t) {
  global $mapping;
  $profile = array('Transport' => $t);
  $monitor = array();
  eval($mapping);
  return $monitor['Method'];
}
$u = 'rtsp://10.0.0.5/s';
$GLOBALS['lines'] = array("tok1, Main, HD, H264, 1920, 1080, 25, RTP-Unicast, $u");
$r = probeProfiles('http://h/onvif', '1', '', '');
check('profile name with a comma is kept', isset($r[0]) ? $r[0]['Name'] : null, 'Main, HD');
check('fields after a comma name are intact', isset($r[0]) ? array($r[0]['Encoding'], $r[0]['Width'], $r[0]['Transport'], $r[0]['Path']) : null, array('H264', '1920', 'RTP-Unicast', $u));

$GLOBALS['lines'] = array(
  "tok1, Main, H264, 1920, 1080, 25, RTP_unicast, $u",
  "tok1, Main, H264, 1920, 1080, 25, RTP-Unicast, $u",
  "tok1, Main, H264, 1920, 1080, 25, RTP-multicast, rtsp://239.0.0.1/s",
  "tok1, Main, H264, 1920, 1080, 25, RTP-Multicast, rtsp://239.0.0.1/s",
  "tok2, Sub, H264, 640, 480, 15, RTP-Unicast, rtsp://10.0.0.5/sub",
);
$r = probeProfiles('http://h/onvif', '1', '', '');
check('duplicate spellings are listed once per profile and cast', count($r), 3);

foreach (array('RTP_unicast' => 'rtpUni', 'RTP-Unicast' => 'rtpUni', 'RTP-multicast' => 'rtpMulti', 'RTP_multicast' => 'rtpMulti', 'RTP-Multicast' => 'rtpMulti', 'weird' => 'rtpRtsp') as $t => $want)
  check("$t maps to $want", methodFor($t), $want);
exit($failures ? 1 : 0);
