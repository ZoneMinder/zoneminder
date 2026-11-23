<?php
// MonitorAction::targetCandidates() offers monitors as action targets. It has
// to respect the same Control permission the rest of the web interface does:
// a monitor the user cannot view Control on must not appear, name or id, or
// the editor leaks the names of cameras the user has no access to.
//
// The ZM classes are stubbed out so this runs without a database. Monitor::find
// returns three monitors; canView('Control', 2) is false, so only 1 and 3 are
// allowed targets.
//
// php tests/php/test_monitor_action_candidates.php [root]   (root defaults to the repo)

namespace ZM {
  class ZM_Object {
    public static function _find($c, $p = array(), $o = array()) { return array(); }
    public static function _find_one($c, $p = array(), $o = array()) { return null; }
  }

  class Control {
    function Id() { return 5; }
    function CanLight() { return true; }
    function CanIndicatorLight() { return false; }
    function CanAudioPlay() { return false; }
    function MinAudioFile() { return null; }
    function MaxAudioFile() { return null; }
  }

  class Monitor {
    private $id;
    function __construct($id) { $this->id = $id; }
    public static function find($params = array()) {
      return array(new Monitor(1), new Monitor(2), new Monitor(3));
    }
    function Id() { return $this->id; }
    function Name() { return $this->id == 2 ? 'Hidden Camera' : 'Camera '.$this->id; }
    function DeviceClass() { return 'Remote'; }
    function Controllable() { return true; }
    function ControlId() { return 5; }
    function Control() { return new Control(); }
  }
}

namespace {
  // The permission the fix is expected to consult. Monitor 2 is denied.
  function canView($area, $mid = false) {
    return !($area == 'Control' and $mid == 2);
  }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

  $stub = sys_get_temp_dir().'/zm_maction_'.getmypid();
  @mkdir($stub, 0700, true);
  foreach (array('database.php', 'Object.php', 'Control.php', 'Monitor.php') as $file)
    file_put_contents($stub.'/'.$file, "<?php\n");
  // First on the path, so MonitorAction.php's require_once()s pick these up
  // rather than the real web/includes classes.
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  require_once $root.'/web/includes/MonitorAction.php';

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $candidates = ZM\MonitorAction::targetCandidates();

  $ids = array();
  foreach ($candidates as $candidate) $ids[] = $candidate['Id'];
  check('only monitors viewable on Control are candidates', $ids, array(1, 3));

  $names = array();
  foreach ($candidates as $candidate) $names[] = $candidate['Name'];
  check('the denied monitor name is not listed', in_array('Hidden Camera', $names, true), false);

  foreach ($candidates as $candidate) {
    check('candidate '.$candidate['Id'].' has an Id', array_key_exists('Id', $candidate), true);
    check('candidate '.$candidate['Id'].' has a Name', array_key_exists('Name', $candidate), true);
    check('candidate '.$candidate['Id'].' has Types', array_key_exists('Types', $candidate), true);
  }

  foreach (glob($stub.'/*.php') as $file) @unlink($file);
  @rmdir($stub);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
