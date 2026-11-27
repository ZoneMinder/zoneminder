<?php
// The download modal showed the size and count of any event id given in eid or
// eids[], whether or not the user may view the monitor it belongs to. It now
// counts and sizes only events the user can view and reports the others as
// invalid.
//
// Run as: php tests/php/test_download_modal_event_view.php [root]

namespace ZM {
  function Error($m) {}
  function Warning($m) {}
  class Filter {
    public static function parse($f) { return null; }
  }
  class Event {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return ($this->id == 5 or $this->id == 6) ? $this->id : null; }
    public function __get($n) { return $n == 'Id' ? $this->Id() : null; }
    public function canView() { return $this->id == 5; }
    public function DiskSpace() { return $this->id == 5 ? 100 : 7000; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  $exports = sys_get_temp_dir().'/zm_download_modal_'.getmypid();
  define('ZM_DIR_EXPORTS', $exports);

  // The modal requires includes/Filter.php relative to the include path.
  $stubdir = sys_get_temp_dir().'/zm_download_modal_stub_'.getmypid();
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
  function human_filesize($n) { return $n.'B'; }

  $cases = array(
    'eids' => array(array('eids' => array('5', '6')), 'Downloading 1 events.  Resulting file should be approximately 100B'),
    'hidden_eid' => array(array('eid' => '6'), 'Invalid event id'),
    'visible_eid' => array(array('eid' => '5'), 'Downloading event 5.'),
  );

  if (isset($argv[2])) {
    $_REQUEST = $cases[$argv[2]][0];
    ob_start();
    include $root.'/web/ajax/modals/download.php';
    echo ob_get_clean();
    exit(0);
  }

  $failures = 0;
  foreach ($cases as $name => $case) {
    $out = (string)shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg($name).' 2>&1');
    if (strpos($out, $case[1]) !== false) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (output lacks '{$case[1]}')\n";
    }
  }
  // A hidden event's size must not appear anywhere.
  $out = (string)shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' hidden_eid 2>&1');
  if (strpos($out, '7000') !== false) {
    $failures++;
    echo "FAIL - the size of a hidden event is shown\n";
  } else {
    echo "ok - the size of a hidden event is not shown\n";
  }
  @rmdir($exports);
  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  exit($failures ? 1 : 0);
}
