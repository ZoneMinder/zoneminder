<?php
// web/includes/monitor_probe.php: the network probe mapped every Remote and Ffmpeg
// monitor by host and returned the match, and probed with its stored user and
// password, for a user who cannot edit that monitor. The map now holds only
// monitors the user can edit.
//
// php tests/php/test_probe_known_monitors_acl.php [root]

namespace ZM {
  function Debug($s) {}
  function urlForLog($s) { return $s; }
  class Monitor {
    public $id, $type, $host, $path;
    function __construct($id, $type, $host, $path) { $this->id = $id; $this->type = $type; $this->host = $host; $this->path = $path; }
    function Id() { return $this->id; }
    function Host() { return $this->host; }
    function Path() { return $this->path; }
    static function find($w) {
      $all = array(
        new Monitor(3, 'Remote', '192.0.2.3', ''),
        new Monitor(7, 'Remote', 'admin:pw@192.0.2.7', ''),
        new Monitor(4, 'Ffmpeg', '', 'rtsp://192.0.2.4/a'),
        new Monitor(8, 'Ffmpeg', '', 'rtsp://u:p@192.0.2.8/a'),
      );
      return array_values(array_filter($all, function ($m) use ($w) { return $m->type == $w['Type']; }));
    }
  }
}

namespace {
  function canEdit($p, $id = 0) { return $id == 0 || $id == 3 || $id == 4; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $src = file_get_contents("$root/web/includes/monitor_probe.php");
  $start = strpos($src, 'function probeKnownMonitors(');
  if ($start === false) {
    // before the fix the map was built inside probeNetwork()
    $a = strpos($src, '  $monitors = array();', strpos($src, 'function probeNetwork('));
    $b = strpos($src, '//ZM\\Debug(print_r($monitors, true));');
    eval('function probeKnownMonitors() {'.substr($src, $a, $b - $a).' return $monitors; }');
  } else {
    $depth = 0;
    for ($i = strpos($src, '{', $start); $i < strlen($src); $i++) {
      if ($src[$i] == '{') $depth++;
      else if ($src[$i] == '}' && --$depth == 0) break;
    }
    eval(substr($src, $start, $i - $start + 1));
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  $map = probeKnownMonitors();
  $ids = array();
  foreach ($map as $host => $m) $ids[$host] = $m->Id();
  ksort($ids);
  check('only editable Remote and Ffmpeg monitors are mapped', $ids, array('192.0.2.3' => 3, '192.0.2.4' => 4));
  exit($failures ? 1 : 0);
}
