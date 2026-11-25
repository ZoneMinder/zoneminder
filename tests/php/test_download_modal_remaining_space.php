<?php
// The download modal printed "Will remain free" through human_filesize(), which
// does not scale a negative size, so a download larger than the free space
// showed as "-5368709120.00B" instead of "-5.00GB".
//
// Run as: php tests/php/test_download_modal_remaining_space.php [root]

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  class Filter {
    public static function parse($f) { return null; }
  }
  class Event {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function __get($n) { return $n == 'Id' ? $this->id : null; }
    public function canView() { return true; }
    public function DiskSpace() { return $GLOBALS['eventSize']; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  $exports = sys_get_temp_dir().'/zm_download_remaining_'.getmypid();
  define('ZM_DIR_EXPORTS', $exports);

  $stubdir = sys_get_temp_dir().'/zm_download_remaining_stub_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canView($area) { return true; }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function validInt($v) { return (int)$v; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v); }
  function translate($s) { return $s; }
  function generateConnKey() { return 1; }
  function getCSRFinputHTML() { return ''; }

  // The real function, so the test sees what the page prints.
  $src = file_get_contents($root.'/web/includes/functions.php');
  if (!preg_match('/^function human_filesize\(.*?^}/ms', $src, $m)) {
    echo "FAIL - human_filesize not found\n";
    exit(1);
  }
  eval($m[0]);

  if (isset($argv[2])) {
    @mkdir($exports, 0700, true);
    $free = disk_free_space($exports);
    // Larger than the free space by 5 GiB, or well under it.
    $GLOBALS['eventSize'] = $argv[2] == 'over' ? (int)($free + 5 * 1024 * 1024 * 1024) : 1024 * 1024;
    $_REQUEST = array('eids' => array('5'));
    ob_start();
    include $root.'/web/ajax/modals/download.php';
    echo ob_get_clean();
    exit(0);
  }

  $failures = 0;
  $run = function($case) use ($root) {
    return (string)shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.$case.' 2>&1');
  };

  $out = $run('over');
  if (preg_match('/Will remain free: -\d+\.\d\d[kMGT]B/', $out)) {
    echo "ok - a shortfall is scaled and signed\n";
  } else {
    $failures++;
    preg_match('/Will remain free: [^<\n]*/', $out, $mm);
    echo "FAIL - a shortfall is not scaled (".($mm[0] ?? 'no line').")\n";
  }

  $out = $run('under');
  if (preg_match('/Will remain free: \d+\.\d\d[kMGTP]B/', $out)) {
    echo "ok - spare space is shown as before\n";
  } else {
    $failures++;
    echo "FAIL - spare space line changed\n";
  }

  @rmdir($exports);
  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  exit($failures ? 1 : 0);
}
