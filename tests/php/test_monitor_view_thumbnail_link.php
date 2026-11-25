<?php
// The thumbnail on the Viewing tab of views/monitor.php links to the watch view only when the
// monitor can be streamed: the user may view streams and the monitor is a web site or is capturing.
// The line was written with "and" / "or", which bind looser than "=", so $stream_available took
// only canView('Stream') and the rest was discarded: the thumbnail of a stopped monitor, or of
// a monitor that is still being added, linked to a watch page with nothing to show.
//
// Run as: php tests/php/test_monitor_view_thumbnail_link.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
if (!preg_match('/^\s*(\$stream_available = [^\n]*;)$/m', $src, $m)) {
  echo "FAIL - could not find the \$stream_available line in monitor.php\n";
  exit(1);
}
$line = $m[1];

class StubMonitor {
  public $type, $fps, $capturing;
  function __construct($type, $fps, $capturing) { $this->type = $type; $this->fps = $fps; $this->capturing = $capturing; }
  function Type() { return $this->type; }
  function CaptureFPS() { return $this->fps; }
  function Capturing() { return $this->capturing; }
}

$canViewStream = true;
function canView($what) { global $canViewStream; return $canViewStream; }

$failures = 0;
function check($name, $monitor, $canView, $want) {
  global $line, $failures, $canViewStream;
  $canViewStream = $canView;
  $stream_available = null;
  eval($line);
  if ((bool)$stream_available === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export((bool)$stream_available, true).")\n";
  }
}

check('capturing monitor links to watch', new StubMonitor('Ffmpeg', '10.00', 'Always'), true, true);
check('web site monitor links to watch', new StubMonitor('WebSite', null, 'Always'), true, true);
check('monitor with no capture fps has no watch link', new StubMonitor('Ffmpeg', null, 'Always'), true, false);
check('monitor with Capturing None has no watch link', new StubMonitor('Ffmpeg', '10.00', 'None'), true, false);
check('user without Stream view gets no link for a capturing monitor', new StubMonitor('Ffmpeg', '10.00', 'Always'), false, false);
check('user without Stream view gets no link for a web site', new StubMonitor('WebSite', null, 'Always'), false, false);
exit($failures ? 1 : 0);
