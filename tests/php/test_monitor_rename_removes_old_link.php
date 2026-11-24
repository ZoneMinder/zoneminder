<?php
// Renaming a monitor makes monitor.php replace the name symlink in each storage area (Name ->
// Id directory). It found the old link by comparing the link's resolved target with
// Path/Id built from the unresolved storage path, so with a storage Path that goes through a
// symlink (a mount point linked into place) nothing matched, the old name link stayed behind
// and the monitor ended up with two. Runs the real web/includes/actions/monitor.php against
// stubbed Monitor, Zone and database layers and a real symlinked storage directory.
//
// php tests/php/test_monitor_rename_removes_old_link.php [path to monitor.php]

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}
  function AuditAction() {}
  function redactForLog($a) { return $a; }

  class Rec { public static $db = array(); public static $path = ''; }

  class Storage {
    public static function find() { return array(new Storage()); }
    public function Path() { return Rec::$path; }
    public function Name() { return 'Default'; }
  }

  class Monitor {
    private $f = array();
    public function __construct($id = null) {
      if ($id and isset(Rec::$db[$id])) $this->f = Rec::$db[$id];
    }
    public function __call($name, $args) {
      if (count($args)) $this->f[$name] = $args[0];
      return isset($this->f[$name]) ? $this->f[$name] : null;
    }
    public function Id($v = null) { return isset($this->f['Id']) ? $this->f['Id'] : null; }
    public function Storage() { return new Storage(); }
    public function zmcControl($m) {}
    public function zmtrackControl($m) {}
    public function sendControlCommand($m) {}
    public function commandFieldChangesNotAllowed($d) { return array(); }
    public function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if (!array_key_exists($k, $this->f) or (string)$this->f[$k] !== (string)$v) $c[$k] = $v;
      }
      return $c;
    }
    public function save($changes) { foreach ($changes as $k => $v) $this->f[$k] = $v; return true; }
    public function get_last_error() { return ''; }
  }

  class Zone {
    public function save($d) { return true; }
    public function get_last_error() { return ''; }
  }
}

namespace {
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) { return true; }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validDevicePath($v) { return $v; }
  function validHtmlStr($v) { return $v; }
  function dbFetchOne($sql, $col = null, $args = array()) { return null; }
  function dbFetchAll($sql, $col = null, $args = array()) { return array(); }
  function dbQuery($sql, $args = array()) { return true; }
  function dbLastError() { return ''; }
  function monitorFormUncheckedDefaults() { return array(); }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/monitor.php';

  // The action pulls its classes in with relative require_once calls.
  $tmpd = sys_get_temp_dir().'/zm_test_rename_'.getmypid();
  mkdir($tmpd.'/includes', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model') as $f) {
    file_put_contents("$tmpd/includes/$f.php", "<?php\n");
  }
  // storage/real holds the events; storage/mount is a symlink to it, as a mount point linked into place.
  mkdir("$tmpd/storage/real/5", 0755, true);
  symlink("$tmpd/storage/real", "$tmpd/storage/mount");
  ZM\Rec::$path = "$tmpd/storage/mount";
  symlink('5', "$tmpd/storage/real/Garage");
  chdir($tmpd);

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  class FakeUser { public function unviewableMonitorIds() { return array(); } }

  $existing = array('Id' => 5, 'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Deleted' => 0,
    'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0);
  ZM\Rec::$db = array(5 => $existing);
  $_REQUEST = array('mid' => '5', 'newMonitor' => array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Door', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480'));
  $error_message = '';
  $redirect = null;
  $user = new FakeUser();
  $action = 'save';
  ob_start();
  include $action_file;
  ob_end_clean();

  check('the new name link points at the monitor directory', @readlink("$tmpd/storage/real/Door"), '5');
  check('the old name link is removed', is_link("$tmpd/storage/real/Garage"), false);
  check('the monitor directory is kept', is_dir("$tmpd/storage/real/5"), true);

  system('rm -rf '.escapeshellarg($tmpd));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
