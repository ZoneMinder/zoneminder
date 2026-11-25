<?php
// downloadEvents() in web/includes/download_functions.php names the merged
// output after the monitor Name. A monitor whose Name contains path
// separators (here '../../../escape') must not make ffmpeg write the merged
// file outside the export directory.
//
// Fake event storage in a temp dir plus a stub ffmpeg first on PATH; the stub
// records the output path it was asked to write (merge runs only) and creates
// it when its directory exists, so an escaping path would show up in the log
// and, if the directories line up, on disk.
//
// Run: php tests/php/test_export_monitor_name_path.php [repo-root]

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
    public function Name() { return '../../../escape'; }
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

  $repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
  $repoRoot = realpath($repoRoot);

  $root = sys_get_temp_dir().'/zm_export_name_'.getmypid();
  mkdir("$root/bin", 0700, true);
  mkdir("$root/exports", 0700);
  mkdir("$root/ev1", 0700);

  // ffmpeg: -hide_banner probe prints a stream line; a merge appends the output
  // path (last argument) to ffmpeg_out.log and creates it only when its
  // directory exists, so an escaping path stays visible in the log.
  file_put_contents("$root/bin/ffmpeg", <<<'SH'
#!/bin/sh
for last; do :; done
if [ "$1" = "-hide_banner" ]; then
  echo "  Stream #0:0: Video: h264 (High), yuv420p, 320x240, 5 fps"
  exit 0
fi
echo "$last" >> "$ZM_TEST_ROOT/ffmpeg_out.log"
d=$(dirname "$last"); [ -d "$d" ] && echo merged > "$last"
SH
  );
  chmod("$root/bin/ffmpeg", 0755);
  putenv("PATH=$root/bin:/usr/bin:/bin");
  putenv("ZM_TEST_ROOT=$root");

  define('ZM_PATH_FFMPEG', "$root/bin/ffmpeg");
  define('ZM_DIR_EXPORTS', "$root/exports");
  define('DIR_EXPORTS_DOWNLOAD', "$root/exports/download");
  set_error_handler(function($no, $str) { return strpos($str, 'already defined') !== false; });

  $src = file_get_contents("$repoRoot/web/includes/functions.php");
  $start = strpos($src, 'function findVideoEventFile');
  $end = strpos($src, "\n}\n", $start) + 3;
  eval(substr($src, $start, $end - $start));
  require_once "$repoRoot/web/includes/download_functions.php";

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
  }

  file_put_contents("$root/ev1/1-video.mp4", 'mp4 data');
  ZM\Event::$fixtures = [
    1 => ['Id'=>1, 'Path'=>"$root/ev1", 'Video'=>'1-video.mp4', 'Start'=>'2026-01-01 10:00:00', 'End'=>'2026-01-01 10:01:00'],
  ];

  try {
    downloadEvents([1], 'Export', 'noArchive', false);
  } catch (\Throwable $e) {
    echo "  threw - ".$e->getMessage()."\n";
  }

  $log = (string)@file_get_contents("$root/ffmpeg_out.log");
  $lines = array_values(array_filter(explode("\n", $log), 'strlen'));

  check('ffmpeg merge run was logged', count($lines) > 0);

  $outside = [];
  foreach ($lines as $l) {
    if (dirname(dirname($l)) !== DIR_EXPORTS_DOWNLOAD) $outside[] = $l;
  }
  check('every ffmpeg output is a direct child of the export dir ('.implode(', ', $outside).')', !$outside);

  $escapedRoot = glob("$root/escape*") ?: [];
  $escapedExports = glob("$root/exports/escape*") ?: [];
  check('no output escaped to the repo root ('.implode(', ', $escapedRoot).')', !$escapedRoot);
  check('no output escaped to the exports dir ('.implode(', ', $escapedExports).')', !$escapedExports);

  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
