<?php
// exportFileList() wrote zmEventDetail/Frames/Images.html into the event directory and
// left them there, so a later export with only Misc ticked included those stale pages.
//
// Run: php tests/php/test_export_misc_stale_html.php [repo root]

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  function Debug($m) {}
  function Fatal($m) { throw new \Exception("Fatal: $m"); }
  class Event {
    public function Id() { return 5; }
    public function canView() { return true; }
    public function Path() { return $GLOBALS['evdir']; }
    public function Relative_Path() { return 'ev'; }
  }
}

namespace {
  $repo = $argv[1] ?? __DIR__.'/../..';
  $root = sys_get_temp_dir().'/zm_misc_stale_'.getmypid();
  $evdir = "$root/ev";
  mkdir($evdir, 0700, true);
  foreach (['00001-capture.jpg', 'ev.mp4', 'note.txt', 'zmEventDetail.html', 'zmEventFrames.html', 'zmEventImages.html'] as $f) {
    file_put_contents("$evdir/$f", 'x');
  }
  set_error_handler(function() { return true; });
  require_once $repo.'/web/skins/classic/includes/export_functions.php';

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
  }

  $ev = new ZM\Event();
  $list = exportFileList($ev, false, false, false, false, true);
  sort($list);
  check('Misc only keeps the other files ('.json_encode($list).')', $list === ['00001-capture.jpg', 'ev.mp4', 'note.txt']);
  foreach (['Detail', 'Frames', 'Images'] as $p) {
    check("stale zmEvent$p.html left out", !in_array("zmEvent$p.html", $list));
  }

  foreach (scandir($evdir) as $e) if ($e[0] != '.') unlink("$evdir/$e");
  rmdir($evdir); rmdir($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
