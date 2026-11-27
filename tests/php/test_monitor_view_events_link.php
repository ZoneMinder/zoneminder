<?php
// The monitor settings page has a header button area for an existing monitor. It should offer a
// link to that monitor's events (the console and watch pages already do), but not for a monitor
// that is still being added and not without the Events view permission.
//
// Run as: php tests/php/test_monitor_view_events_link.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
if (!preg_match('/^\s*(\$events_url = [^\n]*;)$/m', $src, $m)) {
  echo "FAIL - could not find the \$events_url line in monitor.php\n";
  exit(1);
}
$url_line = $m[1];
if (!preg_match('/^\s*(\$events_link = [^\n]*;)$/m', $src, $m)) {
  echo "FAIL - could not find the \$events_link line in monitor.php\n";
  exit(1);
}
$link_line = $m[1];

class StubMonitor {
  public $id;
  function __construct($id) { $this->id = $id; }
  function Id() { return $this->id; }
}

$canViewEvents = true;
function canView($what) { global $canViewEvents; return $what === 'Events' ? $canViewEvents : false; }
function translate($text) { return $text; }

function render($monitor, $canView) {
  global $url_line, $link_line, $canViewEvents;
  $canViewEvents = $canView;
  $events_url = $events_link = null;
  eval($url_line);
  eval($link_line);
  return $events_link;
}

$failures = 0;
function check($name, $monitor, $canView, $want) {
  global $failures;
  $link = render($monitor, $canView);
  $present = $link !== '';
  if ($present !== $want) {
    $failures++;
    echo "FAIL - $name (link ".($present ? 'present' : 'absent').")\n";
    return;
  }
  if ($want) {
    if (strpos($link, 'view=events') === false) {
      $failures++;
      echo "FAIL - $name (does not point at the events view)\n";
    } else if (!preg_match('/%5Bval%5D=(\d+)/', $link, $id) or $id[1] != $monitor->Id()) {
      $failures++;
      echo "FAIL - $name (does not filter to the monitor id)\n";
    } else if (strpos($link, 'title="Events"') === false) {
      $failures++;
      echo "FAIL - $name (link is not labelled Events)\n";
    } else {
      echo "ok - $name\n";
    }
  } else {
    echo "ok - $name\n";
  }
}

check('existing monitor links to its events', new StubMonitor(7), true, true);
check('new monitor has no events link', new StubMonitor(0), true, false);
check('user without Events view gets no events link', new StubMonitor(7), false, false);
exit($failures ? 1 : 0);
