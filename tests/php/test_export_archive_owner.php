<?php
// Round trip exportEvents() (web/skins/classic/includes/export_functions.php)
// into web/views/archive.php (web/includes/download_functions.php):
//
//   1. exportEvents() as user 1 returns an archive link; the named archive
//      must exist in ZM_DIR_EXPORTS.
//   2. archive.php served as user 1 must output that archive (the owner is
//      allowed to fetch it).
//   3. served as user 2 the same archive must produce no output and must be
//      left on disk (the non-owner is refused).
//   4. with no "file" parameter, user 1 must be served u1_zmExport_K.zip if it
//      exists, while user 2 must not be served it.
//
// archive.php streams with readfile() and starts by discarding every output
// buffer, so the include is run in a child copy of this script whose stdout is
// captured. The child only includes archive.php with the same stubs.
//
// Run: php tests/php/test_export_archive_owner.php [repo-root]

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
      $this->f = self::$fixtures[$id] ?? ['Id' => null];
      $this->StartDateTime = $this->f['Start'] ?? '';
    }
    public function Id() { return $this->f['Id']; }
    public function canView() { return !empty($this->f['Id']); }
    public function MonitorId() { return $this->f['MonitorId'] ?? 1; }
    public function DefaultVideo() { return $this->f['Video'] ?? ''; }
    public function Path() { return $this->f['Path'] ?? ''; }
    public function Relative_Path() { return basename($this->f['Path'] ?? ''); }
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
    function Username() { return 'user'.$this->id; }
    function __call($n, $a) { return 'u'; }
  }
  function canView($area, $mid = false) { return true; }
  function to_string($t) { return empty($t) ? '' : (is_array($t) ? implode(', ', $t) : strval($t)); }
  function deletePath($p) {
    if (is_dir($p) and !is_link($p)) {
      foreach (scandir($p) as $e) if ($e != '.' and $e != '..') deletePath("$p/$e");
      rmdir($p);
    } else {
      @unlink($p);
    }
  }

  // Child mode: include archive.php and let readfile() reach the process
  // stdout. Errors are sent to stderr so they cannot be mistaken for archive
  // bytes.
  function runChild($argv) {
    ini_set('display_errors', 'stderr');
    $repoRoot = $argv[2];
    $root = $argv[3];
    $uid = (int)$argv[4];
    $type = $argv[5];
    $connkey = $argv[6];
    $file = $argv[7] ?? '-';

    define('ZM_DIR_EXPORTS', "$root/exports");
    $GLOBALS['user'] = new FakeUser($uid);
    $_REQUEST = ['type' => $type, 'connkey' => $connkey];
    if ($file !== '-') $_REQUEST['file'] = $file;

    set_include_path("$repoRoot/web".PATH_SEPARATOR.get_include_path());
    ob_start();
    include $repoRoot.'/web/views/archive.php';
    // Refused requests never touch the buffer; return anything it caught.
    $buffered = ob_get_clean();
    if ($buffered) echo $buffered;
  }

  if (($argv[1] ?? '') === '--serve') {
    runChild($argv);
    exit(0);
  }

  $repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
  if ($repoRoot !== false) $repoRoot = realpath($repoRoot) ?: $repoRoot;

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok  - $label\n"; } else { $failures++; echo "  NOT OK - $label\n"; }
  }

  $root = sys_get_temp_dir().'/zm_export_archive_owner_'.getmypid();
  if (file_exists($root)) deletePath($root);
  mkdir("$root/bin", 0700, true);
  mkdir("$root/exports", 0700);
  mkdir("$root/ev1", 0700);

  // tar/zip stubs: append "entry <last arg>" to the archive named by the first
  // non-option argument / --file=. Nothing real is archived.
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
  file_put_contents("$root/bin/ffmpeg", "#!/bin/sh\nexit 0\n");
  file_put_contents("$root/bin/gzip", "#!/bin/sh\nexit 0\n");
  foreach (['ffmpeg', 'tar', 'zip', 'gzip'] as $p) chmod("$root/bin/$p", 0755);
  putenv("PATH=$root/bin:/usr/bin:/bin");
  putenv("ZM_TEST_ROOT=$root");

  define('ZM_PATH_FFMPEG', "$root/bin/ffmpeg");
  define('ZM_PATH_WEB', "$repoRoot/web");
  define('ZM_SKIN_PATH', 'skins/classic');
  define('ZM_DIR_EXPORTS', "$root/exports");

  require_once "$repoRoot/web/skins/classic/includes/export_functions.php";

  // Pull the "file" argument out of "?view=archive&type=zip&connkey=K&file=...".
  function archiveFile($r) {
    $q = [];
    $parts = explode('?', (string)$r, 2);
    parse_str($parts[1] ?? '', $q);
    $f = isset($q['file']) ? (string)$q['file'] : '';
    if ($f !== '' && strpos($f, '%') !== false) $f = urldecode($f);
    return $f;
  }

  // exportEvents() for the one fake event, as $uid.
  function runExport($uid) {
    $GLOBALS['user'] = new FakeUser($uid);
    try {
      return exportEvents([1], 'K', false, false, false, true, false, 'zip', false);
    } catch (\Throwable $e) {
      return 'threw: '.$e->getMessage();
    }
  }

  // Include archive.php in a child process and return its stdout.
  function serve($repoRoot, $root, $uid, $type, $connkey, $file) {
    $cmd = [PHP_BINARY, __FILE__, '--serve', $repoRoot, $root, (string)$uid, $type, $connkey, $file];
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $desc, $pipes);
    if (!is_resource($proc)) return '';
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    return (string)$out;
  }

  file_put_contents("$root/ev1/1-video.mp4", 'mp4 data');
  ZM\Event::$fixtures = [
    1 => ['Id' => 1, 'Path' => "$root/ev1", 'Video' => '1-video.mp4',
          'Start' => '2026-01-01 10:00:00', 'End' => '2026-01-01 10:01:00'],
  ];

  // 1. exportEvents() names an archive that exists in ZM_DIR_EXPORTS.
  $link = runExport(1);
  $prefix = '?view=archive&type=zip&connkey=K&file=';
  check('user 1 gets an archive link ('.var_export($link, true).')',
    is_string($link) && strpos($link, $prefix) === 0);
  $file = archiveFile($link);
  $path = ZM_DIR_EXPORTS.'/'.$file;
  check('the returned archive exists in ZM_DIR_EXPORTS ('.var_export($file, true).')',
    $file !== '' && file_exists($path));
  $content = $file !== '' && file_exists($path) ? file_get_contents($path) : '';
  check('the archive is not empty', $content !== '');

  // 2. The owner is served the archive.
  $out = serve($repoRoot, $root, 1, 'zip', 'K', $file);
  check('the owner is served the archive content', $out !== '' && trim($out) === trim($content));

  // 3. Re-create it and confirm another user is refused and the file survives.
  $link = runExport(1);
  $file = archiveFile($link);
  $path = ZM_DIR_EXPORTS.'/'.$file;
  $out = serve($repoRoot, $root, 2, 'zip', 'K', $file);
  check('another user is refused the archive (no output)', $out === '');
  check('the refused archive is left on disk', $file !== '' && file_exists($path));

  // 4. No "file" parameter: archive.php falls back to zmExport_<connkey>.zip
  //    owned by the requesting user.
  $fallback = ZM_DIR_EXPORTS.'/u1_zmExport_K.zip';
  file_put_contents($fallback, 'fallback archive data');
  $out = serve($repoRoot, $root, 1, 'zip', 'K', '-');
  check('user 1 is served u1_zmExport_K.zip when file is unset', trim($out) === 'fallback archive data');
  file_put_contents($fallback, 'fallback archive data');
  $out = serve($repoRoot, $root, 2, 'zip', 'K', '-');
  check('user 2 is not served u1_zmExport_K.zip when file is unset', $out === '');
  check('the default archive is left on disk after the refusal', file_exists($fallback));

  chdir('/');
  deletePath($root);
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
