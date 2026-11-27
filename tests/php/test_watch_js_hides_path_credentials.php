<?php
// views/js/watch.js.php prints a monitorData[] entry per monitor, Path and
// SecondPath included. skin.js builds the stream address from them, so they
// must stay non-empty, but a camera URL can carry user:password@ and the
// monitorData array is visible to anyone who can view the monitor, not only to
// users who may edit it. Path and SecondPath must therefore lose the
// userinfo for a monitor the user cannot edit, and stay as stored for one the
// user can.
//
// The monitor loop of the view script is evaluated here with fake monitors; no
// database or web server is needed.
// Run as: php tests/php/test_watch_js_hides_path_credentials.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

// The real redaction helper. It lives in the ZM namespace, so the view must call ZM\removeUserinfo().
require_once $root.'/web/includes/log_redact.php';

function validJsStr($s) { return addslashes($s); }
define('ZM_MIN_STREAMING_PORT', 0);

class FakeMon {
  private $id, $path, $second, $editable;
  function __construct($id, $path, $second, $editable) {
    $this->id = $id;
    $this->path = $path;
    $this->second = $second;
    $this->editable = $editable;
  }
  function Id() { return $this->id; }
  function Name() { return 'Monitor '.$this->id; }
  function ServerId() { return '1'; }
  function connKey() { return 1; }
  function ViewWidth() { return 640; }
  function ViewHeight() { return 480; }
  function RTSP2WebEnabled() { return 0; }
  function DefaultPlayer() { return 'auto'; }
  function RTSPServer() { return false; }
  function StreamChannel() { return '0'; }
  function Go2RTCEnabled() { return 0; }
  function Path() { return $this->path; }
  function SecondPath() { return $this->second; }
  function Restream() { return false; }
  function JanusEnabled() { return 0; }
  function UrlToIndex($p = '') { return '/zm/index.php'; }
  function UrlToZMS($p = '') { return '/cgi-bin/nph-zms'; }
  function Type() { return 'Ffmpeg'; }
  function Capturing() { return 'Always'; }
  function Refresh() { return ''; }
  function Janus_Pin() { return ''; }
  function StreamReplayBuffer() { return 0; }
  function Controllable() { return false; }
  function getStreamMode() { return 'none'; }
  function WhatDisplay() { return 'events'; }
  function canEdit() { return $this->editable; }
}

$source = file_get_contents($root.'/web/skins/classic/views/js/watch.js.php');
if (!preg_match('/^var monitorData = new Array\(\);.*?^\} \/\/ end foreach monitor\n\?>/ms', $source, $m)) {
  echo "FAIL - watch.js.php has no monitorData loop\n";
  exit(1);
}

$monitorCreds = 'rtsp://admin:hunter2@10.0.0.9/live';
$monitorCredsSub = 'rtsp://admin:hunter2@10.0.0.9/sub';
$monitors = array(
  new FakeMon(1, $monitorCreds, $monitorCredsSub, false),
  new FakeMon(2, $monitorCreds, $monitorCredsSub, true),
  new FakeMon(3, 'rtsp://10.0.0.8/live', '', false),
);
$monitorsExtraData = array();
foreach ($monitors as $monitor) {
  $monitorsExtraData[$monitor->Id()] = array('StreamHTML' => '', 'urlForAllEvents' => '', 'ptzControls' => '');
}

// The chunk starts as plain output, then opens <?php for the foreach.
$cwd = getcwd();
chdir($root.'/web');
ob_start();
eval('?>'.$m[0]);
$out = ob_get_clean();
chdir($cwd);

// Entry per monitor, split apart: each part holds the fields of one monitor.
$parts = array();
foreach (explode('monitorData[monitorData.length] = {', $out) as $part) {
  if (!preg_match("/'id': (\d+),/", $part, $idm)) continue;
  preg_match("/'Path':'(.*?)'/", $part, $pm);
  preg_match("/'SecondPath':'(.*?)'/", $part, $sm);
  $parts[(int)$idm[1]] = array(
    'Path' => isset($pm[1]) ? $pm[1] : null,
    'SecondPath' => isset($sm[1]) ? $sm[1] : null,
  );
}

check('one entry per monitor', count($parts), 3);

// Monitor 1: viewer only, the stored paths carry admin:hunter2@.
$p1 = isset($parts[1]) ? $parts[1]['Path'] : null;
$s1 = isset($parts[1]) ? $parts[1]['SecondPath'] : null;
check('monitor 1 Path has no password', strpos((string)$p1, 'hunter2'), false);
check('monitor 1 Path has no user', strpos((string)$p1, 'admin:'), false);
check('monitor 1 Path is still the camera URL', strpos((string)$p1, 'rtsp://10.0.0.9') === 0, true);
check('monitor 1 SecondPath has no password', strpos((string)$s1, 'hunter2'), false);
check('monitor 1 SecondPath has no user', strpos((string)$s1, 'admin:'), false);
check('monitor 1 SecondPath is still the camera URL', strpos((string)$s1, 'rtsp://10.0.0.9') === 0, true);

// Monitor 2: the user may edit it, so the paths are printed as stored.
check('monitor 2 Path keeps the userinfo', isset($parts[2]) ? $parts[2]['Path'] : null, $monitorCreds);
check('monitor 2 SecondPath keeps the userinfo', isset($parts[2]) ? $parts[2]['SecondPath'] : null, $monitorCredsSub);

// Monitor 3: nothing to remove; SecondPath is empty and must not become a
// placeholder (skin.js tests it for truthiness).
check('monitor 3 Path is unchanged', isset($parts[3]) ? $parts[3]['Path'] : null, 'rtsp://10.0.0.8/live');
check('monitor 3 empty SecondPath stays empty', isset($parts[3]) ? $parts[3]['SecondPath'] : null, '');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
