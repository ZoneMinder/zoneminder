<?php
// downloadEvents() export ownership in web/includes/download_functions.php,
// run against fake event storage in a temp dir with stub ffmpeg/tar/zip/gzip
// programs first on PATH (nothing real is encoded or archived).
//
//  1. two different users exporting the same event under the same file name
//     must get different archives; downloads used to be named after the
//     requested file name only, so both users shared one archive
//  2. each user's archive must exist on disk under the returned name
//  3. exportNameBelongsToUser() must let the owner fetch the export and
//     reject another user (downloads used to be readable by anyone)
//
// Run: php tests/php/test_export_owner.php [repo-root]

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
  class FakeUser {
    public $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    function __call($n, $a) { return 'u'; }
  }
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
  if ($repoRoot !== false) $repoRoot = realpath($repoRoot) ?: $repoRoot;

  $root = sys_get_temp_dir().'/zm_export_owner_'.getmypid();
  mkdir("$root/bin", 0700, true);
  mkdir("$root/exports", 0700);
  mkdir("$root/ev1", 0700);

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

  // Pull the "file" argument back out of a "?view=download&type=zip&file=..."
  // result, decoding it if the name is still percent-encoded.
  function exportFile($r) {
    $q = [];
    $parts = explode('?', (string)$r, 2);
    parse_str($parts[1] ?? '', $q);
    $f = isset($q['file']) ? (string)$q['file'] : '';
    if ($f !== '' && strpos($f, '%') !== false) $f = urldecode($f);
    return $f;
  }

  file_put_contents("$root/ev1/1-video.mp4", 'mp4 data');
  ZM\Event::$fixtures = [
    1 => ['Id'=>1, 'Path'=>"$root/ev1", 'Video'=>'1-video.mp4', 'Start'=>'2026-01-01 10:00:00', 'End'=>'2026-01-01 10:01:00'],
  ];

  function run($uid, $eids, $format = 'zip') {
    $GLOBALS['user'] = new FakeUser($uid);
    @unlink("$GLOBALS[root]/concat.log");
    try {
      return downloadEvents($eids, 'Export', $format, false);
    } catch (\Throwable $e) {
      return 'threw: '.$e->getMessage();
    }
  }

  // 1. two users exporting the same event get different archives
  $r1 = run(1, [1]);
  $r2 = run(2, [1]);
  check('different users get different archive names ('.var_export($r1, true).' vs '.var_export($r2, true).')', $r1 !== $r2);
  $prefix = '?view=download&type=zip&file=';
  check('user 1 gets a zip download link ('.var_export($r1, true).')',
    is_string($r1) && $r1 !== false && strpos($r1, $prefix) === 0);
  check('user 2 gets a zip download link ('.var_export($r2, true).')',
    is_string($r2) && $r2 !== false && strpos($r2, $prefix) === 0);
  $f1 = exportFile($r1);
  $f2 = exportFile($r2);

  // 2. both archives exist on disk under the names that were returned
  check('each user has their own archive file',
    $f1 !== '' && file_exists(DIR_EXPORTS_DOWNLOAD.'/'.$f1)
    && $f2 !== '' && file_exists(DIR_EXPORTS_DOWNLOAD.'/'.$f2));

  // 3. only the owner may fetch the export
  $GLOBALS['user'] = new FakeUser(1);
  check('owner can fetch', function_exists('exportNameBelongsToUser') && exportNameBelongsToUser($f1));
  $GLOBALS['user'] = new FakeUser(2);
  check('another user cannot fetch', function_exists('exportNameBelongsToUser') && !exportNameBelongsToUser($f1));

  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
