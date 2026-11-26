<?php
// exportEvents() names its working directory <root>_<connkey> and archives that
// directory, but zmFileList.txt inside the archive listed <root><connkey>/...
// (no underscore), paths that exist nowhere in the archive.
//
// Run: php tests/php/test_export_filelist_paths.php

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  function Debug($m) {}
  function Fatal($m) { throw new \Exception("Fatal: $m"); }
  class Event {
    public $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function canView() { return true; }
    public function Path() { return $GLOBALS['evdir']; }
    public function Relative_Path() { return 'ev'; }
  }
}

namespace {
  function canView($a) { return true; }
  function deletePath($p) {
    if (is_dir($p) and !is_link($p)) {
      foreach (scandir($p) as $e) if ($e != '.' and $e != '..') deletePath("$p/$e");
      rmdir($p);
    } else {
      @unlink($p);
    }
  }

  $root = sys_get_temp_dir().'/zm_filelist_'.getmypid();
  $evdir = "$root/ev";
  mkdir("$root/bin", 0700, true);
  mkdir("$root/exports", 0700);
  mkdir($evdir, 0700);
  file_put_contents("$evdir/note.txt", 'x');
  // zip stub: keep a copy of the list file the real archive would contain
  file_put_contents("$root/bin/zip", "#!/bin/sh\ncp \"\$ZM_TEST_ROOT\"/exports/u0_zmExport_*/zmFileList.txt \"\$ZM_TEST_ROOT/list.txt\"\n");
  chmod("$root/bin/zip", 0755);
  putenv("PATH=$root/bin:/usr/bin:/bin");
  putenv("ZM_TEST_ROOT=$root");
  define('ZM_DIR_EXPORTS', "$root/exports");
  define('ZM_PATH_WEB', "$root/web");
  define('ZM_SKIN_PATH', 'skins/classic');
  set_error_handler(function() { return true; });
  require_once __DIR__.'/../../web/skins/classic/includes/export_functions.php';

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
  }

  $r = exportEvents([5], '123', false, false, false, false, true, 'zip', false, false, 'zmExport');
  check('export succeeds ('.var_export($r, true).')', is_string($r));
  $lines = file("$root/list.txt", FILE_IGNORE_NEW_LINES);
  check('list file was captured', is_array($lines));
  check('entry is under the archived directory ('.var_export($lines[0] ?? null, true).')', ($lines[0] ?? '') === 'u0_zmExport_123/5/note.txt');

  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
