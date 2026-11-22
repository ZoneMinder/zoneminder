<?php
// Deleting the last event of a Deep storage area must only prune the empty
// date directories below <storage>/<monitorid>, never the monitor directory,
// the Storage root, or anything above it. A directory that still holds a
// dotfile (another event's .<id> link) is not empty.
//
// Run as: php tests/php/test_event_deep_delete_prunes_within_monitor.php

namespace ZM;

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function remove_tree($path) {
  if (is_link($path) or is_file($path)) {
    @unlink($path);
  } else if (is_dir($path)) {
    foreach (scandir($path) as $entry) {
      if ($entry === '.' or $entry === '..') continue;
      remove_tree($path.'/'.$entry);
    }
    @rmdir($path);
  }
}

$stubdir = sys_get_temp_dir().'/zm_event_deep_prune_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

require_once __DIR__.'/../../web/includes/Event.php';

$root = sys_get_temp_dir().'/zm_event_deep_prune_'.getmypid();
remove_tree($root);
mkdir($root.'/3/26/03/05/23/58/07', 0700, true);
file_put_contents($root.'/3/26/03/05/23/58/07/event.mp4', 'x');
symlink('23/58/07', $root.'/3/26/03/05/.7');
symlink('23/59/08', $root.'/3/26/03/05/.8');

mkdir($root.'/3/26/03/06/10/20/30', 0700, true);
file_put_contents($root.'/3/26/03/06/10/20/30/event.mp4', 'x');
symlink('10/20/30', $root.'/3/26/03/06/.9');

// Event 9 is the only event in day 06, so the whole date branch is pruned.
$event9 = $root.'/3/26/03/06/10/20/30';
remove_tree($event9);
unlink($root.'/3/26/03/06/.9');
Event::pruneEmptyEventDirs($event9, $root);
check('event 9 dir removed', is_dir($event9), false);
check('empty day 06 removed', is_dir($root.'/3/26/03/06'), false);
check('month dir kept because day 05 remains', is_dir($root.'/3/26/03'), true);

// Event 7 leaves another event's dotfile link in day 05, so day 05 and its
// parents must stay even though the time directories are empty.
$event7 = $root.'/3/26/03/05/23/58/07';
remove_tree($event7);
unlink($root.'/3/26/03/05/.7');
Event::pruneEmptyEventDirs($event7, $root);
check('event 7 dir removed', is_dir($event7), false);
check('empty seconds dir removed', is_dir($root.'/3/26/03/05/23/58'), false);
check('empty hour dir removed', is_dir($root.'/3/26/03/05/23'), false);
check('day dir with another event link kept', is_dir($root.'/3/26/03/05'), true);
check('other event link kept', is_link($root.'/3/26/03/05/.8'), true);
check('monitor dir kept', is_dir($root.'/3'), true);
check('storage root kept', is_dir($root), true);

remove_tree($root);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  @unlink($stubdir.'/'.$stub);
}
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
