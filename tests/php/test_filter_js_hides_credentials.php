<?php
// The filter page script printed whole monitor and storage area rows into
// const monitors / const storageareas. Monitor rows carry camera credentials
// (User, Pass, ControlAddress, Path) and storage area rows carry the s3 key
// and secret in Url. The page script only reads Name, so both lists must be
// narrowed to Name before they are printed. Checked by running the page
// script with stand-in monitor, storage area and filter objects; no database
// or source text matching involved.
// Run as: php tests/php/test_filter_js_hides_credentials.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function validJsStr($s) { return $s; }
function translate($s) { return $s; }

class FakeMonitor {
  public $Id;
  public $Name;
  public $User = 'admin';
  public $Pass = 'hunter2';
  public $ControlAddress = 'root:camsecret@10.0.0.5';
  public $Path = 'rtsp://u:p@cam/x';
  public function __construct($id, $name) { $this->Id = $id; $this->Name = $name; }
  public function Id() { return $this->Id; }
  public function Name() { return $this->Name; }
}

class FakeStorage {
  public $Id;
  public $Name;
  public $Url = 's3://AKIAKEY:SECRETKEY@host/bucket';
  public function __construct($id, $name) { $this->Id = $id; $this->Name = $name; }
  public function Id() { return $this->Id; }
  public function Name() { return $this->Name; }
}

class FakeFilter implements JsonSerializable {
  public function Id() { return 7; }
  public function jsonSerialize(): mixed { return array('Id' => 7); }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$filter = new FakeFilter;
$monitors = array(1 => new FakeMonitor(1, 'Porch'), 2 => new FakeMonitor(2, 'Yard'));
$storageareas = array(
  '' => array('Name' => 'NULL Unspecified'),
  '0' => array('Name' => 'Zero'),
  3 => new FakeStorage(3, 'Bucket'),
);
$booleanValues = array();
$filterQuery = '';
$sortQuery = '';
$conjunctionTypes = array('and' => 'And');
$opTypes = array('=' => 'Equals');
$tags_opTypes = array();
$archiveTypes = array();
$weekdays = array();
$states = array();
$servers = array();
$availableTags = array();
$zones = array();

ob_start();
include $root.'/web/skins/classic/views/js/filter.js.php';
$out = ob_get_clean();

preg_match('/^const monitors = (.*);$/m', $out, $m);
$monitorsJs = json_decode(isset($m[1]) ? $m[1] : 'null', true);
preg_match('/^const storageareas = (.*);$/m', $out, $m);
$storageareasJs = json_decode(isset($m[1]) ? $m[1] : 'null', true);
preg_match('/^const sorted_monitor_ids = (.*);$/m', $out, $m);
$sortedIds = json_decode(isset($m[1]) ? $m[1] : 'null', true);

check('monitor 1 is listed by Name only', isset($monitorsJs[1]) ? $monitorsJs[1] : null, array('Name' => 'Porch'));
check('monitor 2 is listed by Name only', isset($monitorsJs[2]) ? $monitorsJs[2] : null, array('Name' => 'Yard'));
check("storage area '' is listed by Name only", isset($storageareasJs['']) ? $storageareasJs[''] : null, array('Name' => 'NULL Unspecified'));
check("storage area '0' is listed by Name only", isset($storageareasJs[0]) ? $storageareasJs[0] : null, array('Name' => 'Zero'));
check('storage area 3 is listed by Name only', isset($storageareasJs[3]) ? $storageareasJs[3] : null, array('Name' => 'Bucket'));

check('the monitor password is not printed', strpos($out, 'hunter2') === false, true);
check('the address in ControlAddress is not printed', strpos($out, 'camsecret') === false, true);
check('the storage secret key is not printed', strpos($out, 'SECRETKEY') === false, true);
check('the storage access key is not printed', strpos($out, 'AKIAKEY') === false, true);
check('the monitor stream path is not printed', strpos($out, 'rtsp://u:p@cam') === false, true);

check('sorted_monitor_ids is still [1, 2]', $sortedIds, array(1, 2));

exit($failures ? 1 : 0);
