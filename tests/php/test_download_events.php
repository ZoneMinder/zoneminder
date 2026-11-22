<?php
// downloadEvents() in web/includes/download_functions.php, run against fake
// event storage in a temp dir with stub ffmpeg/tar/zip/gzip programs first on
// PATH (nothing real is encoded or archived).
//
//  1. an event the user cannot view (deleted, or on a hidden monitor) is
//     skipped; it used to die on $user['Username'] because $user is an object
//  2. an event recorded as .mkv is merged into the download; only .mp4 files
//     were looked for, so the event was dropped and the download failed
//  3. a left-over archive of the same name is replaced; tar/zip appended to it,
//     so the old events came back in the new download
//
// Run: php tests/php/test_download_events.php

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  function Debug($m) {}
  function Info($m) {}
  function Fatal($m) { throw new \Exception("Fatal: $m"); }

  class Event {
    public static $fixtures = [];
    public $f;
    public $StartDateTime;
    public function __construct($id) {
      $this->f = self::$fixtures[$id] ?? ['Id'=>null];
      $this->StartDateTime = $this->f['Start'] ?? '';
    }
    public function Id() { return $this->f['Id']; }
    public function canView() { return !empty($this->f['Id']); }
    public function MonitorId() { return $this->f['MonitorId'] ?? 1; }
    public function DefaultVideo() { return $this->f['Video'] ?? ''; }
    public function Path() { return $this->f['Path'] ?? ''; }
    public function StartDateTime() { return $this->StartDateTime; }
    public function StartDateTimeSecs() { return strtotime($this->StartDateTime); }
    public function EndDateTime() { return $this->f['End'] ?? ''; }
    public function EndDateTimeSecs() { return strtotime($this->f['End'] ?? '') ?: 0; }
    public function GenerateVideo() { return null; }
  }
  class Monitor {
    public static function find_one($p) { return new Monitor(); }
    public function Name() { return 'Front'; }
  }
}

namespace {
  class User { function __call($n, $a) { return 'admin'; } } // like ZM\User: __call accessors, no ArrayAccess
  $user = new User();
  function canView($a) { return true; }
  function to_string($t) { return empty($t) ? '' : (is_array($t) ? implode(', ', $t) : strval($t)); }
  function deletePath($p) {
    if (is_dir($p) and !is_link($p)) {
      foreach (scandir($p) as $e) if ($e != '.' and $e != '..') deletePath("$p/$e");
      rmdir($p);
    } else {
      @unlink($p);
    }
  }

  $root = sys_get_temp_dir().'/zm_dl_events_'.getmypid();
  mkdir("$root/bin", 0700, true);
  mkdir("$root/exports", 0700);
  mkdir("$root/ev1", 0700);
  mkdir("$root/ev2", 0700);

  // ffmpeg: -hide_banner probe prints a stream line; a merge copies the list
  // into a log and creates the output (last argument).
  file_put_contents("$root/bin/ffmpeg", <<<'SH'
#!/bin/sh
for last; do :; done
if [ "$1" = "-hide_banner" ]; then
  echo "  Stream #0:0: Video: h264 (High), yuv420p, 320x240, 5 fps"
  exit 0
fi
cat event_files.txt >> "$ZM_TEST_ROOT/concat.log"
echo merged > "$last"
SH
  );
  // tar/zip: append "entry <last arg>" to the archive named by --file= / the
  // first non-option argument.
  file_put_contents("$root/bin/tar", <<<'SH'
#!/bin/sh
for a; do
  case "$a" in
    --version) echo "tar (GNU tar) 1.34"; exit 0;;
    --file=*) arc="${a#--file=}";;
  esac
  last="$a"
done
echo "entry $last" >> "$arc"
SH
  );
  file_put_contents("$root/bin/zip", <<<'SH'
#!/bin/sh
for a; do
  case "$a" in -*) ;; *) [ -z "$arc" ] && arc="$a";; esac
  last="$a"
done
echo "entry $last" >> "$arc"
SH
  );
  file_put_contents("$root/bin/gzip", "#!/bin/sh\nexit 0\n");
  foreach (['ffmpeg', 'tar', 'zip', 'gzip'] as $p) chmod("$root/bin/$p", 0755);
  putenv("PATH=$root/bin:/usr/bin:/bin");
  putenv("ZM_TEST_ROOT=$root");

  define('ZM_PATH_FFMPEG', "$root/bin/ffmpeg");
  define('ZM_DIR_EXPORTS', "$root/exports");
  define('DIR_EXPORTS_DOWNLOAD', "$root/exports/download");
  set_error_handler(function($no, $str) { return strpos($str, 'already defined') !== false; });

  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  $start = strpos($src, 'function findVideoEventFile');
  $end = strpos($src, "\n}\n", $start) + 3;
  eval(substr($src, $start, $end - $start));
  require_once __DIR__.'/../../web/includes/download_functions.php';

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
  }

  file_put_contents("$root/ev1/1-video.mp4", 'mp4 data');
  file_put_contents("$root/ev2/2-video.mkv", 'mkv data');
  ZM\Event::$fixtures = [
    1 => ['Id'=>1, 'Path'=>"$root/ev1", 'Video'=>'1-video.mp4', 'Start'=>'2026-01-01 10:00:00', 'End'=>'2026-01-01 10:01:00'],
    2 => ['Id'=>2, 'Path'=>"$root/ev2", 'Video'=>'2-video.mkv', 'Start'=>'2026-01-01 11:00:00', 'End'=>'2026-01-01 11:01:00'],
  ];

  function run($eids, $format = 'zip') {
    @unlink("$GLOBALS[root]/concat.log");
    try {
      return downloadEvents($eids, 'Export', $format, false);
    } catch (\Throwable $e) {
      return 'threw: '.$e->getMessage();
    }
  }

  // 1. an unviewable event is skipped, the viewable one is exported
  $r = run([1, 99]);
  check('unviewable event is skipped without an error ('.var_export($r, true).')', $r === '?view=download&type=zip&file=u0_Export.zip');
  check('viewable event was merged', strpos((string)@file_get_contents("$root/concat.log"), "$root/ev1/1-video.mp4") !== false);
  @unlink("$root/exports/download/u0_Export.zip");

  // 2. an mkv event is merged
  $r = run([2]);
  check('mkv event gives a download ('.var_export($r, true).')', $r === '?view=download&type=zip&file=u0_Export.zip');
  check('mkv file is in the merge list', strpos((string)@file_get_contents("$root/concat.log"), "$root/ev2/2-video.mkv") !== false);
  @unlink("$root/exports/download/u0_Export.zip");

  // 3. a left-over archive is not appended to
  @mkdir(DIR_EXPORTS_DOWNLOAD, 0700, true);
  file_put_contents(DIR_EXPORTS_DOWNLOAD.'/u0_Export.zip', "OLD\n");
  run([1]);
  $zip = (string)@file_get_contents(DIR_EXPORTS_DOWNLOAD.'/u0_Export.zip');
  check('old archive content is gone from the new zip', strpos($zip, 'OLD') === false);
  check('new zip holds the merged video', strpos($zip, 'entry') !== false);
  @unlink(DIR_EXPORTS_DOWNLOAD.'/u0_Export.zip');
  file_put_contents(DIR_EXPORTS_DOWNLOAD.'/u0_Export.tar', "OLD\n");
  run([1], 'tar');
  $tar = (string)@file_get_contents(DIR_EXPORTS_DOWNLOAD.'/u0_Export.tar');
  check('old archive content is gone from the new tar', strpos($tar, 'OLD') === false);

  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
