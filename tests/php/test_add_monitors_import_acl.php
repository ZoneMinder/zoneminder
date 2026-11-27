<?php
// web/ajax/add_monitors.php
//  - The CSV import offered any existing monitor found for a URL, even one the
//    user cannot edit, leaking its settings in the response.
//
// Runs the action part of the real file with stubbed helpers.
// php tests/php/test_add_monitors_import_acl.php [root]

namespace ZM {
  function Info($s) {}
  function Debug($s) {}
  function Warning($s) {}
  function urlForLog($s) { return $s; }
  class Monitor {
    public static $find_id = 0;
    public $marker = 'stub';
    private $id;
    function __construct($id = 0) { $this->id = $id; }
    function Id() { return $this->id; }
    static function find($w) { return array(new Monitor(self::$find_id)); }
  }
  class Group { static function find_one($w) { return null; } }
}

namespace {
  class Responded extends Exception { public $data; function __construct($d) { $this->data = $d; parent::__construct('responded'); } }
  function canEdit($p, $id = 0) { return $id == 0 || $id == 3; }
  function ajaxResponse($d) { throw new Responded($d); }
  function ajaxError($m) { throw new Responded(array('error' => $m)); }
  function validHtmlStr($s) { return $s; }
  function probe($bits) { return array(); }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $src = file_get_contents("$root/web/ajax/add_monitors.php");
  $start = strpos($src, "if (canEdit('Monitors')) {");
  $code = substr($src, $start);
  $code = str_replace('is_uploaded_file(', 'is_file(', $code);
  $code = preg_replace('/\?>\s*$/', '', $code);

  $passes = 0;
  $failures = 0;
  function check($name, $got, $want) {
    global $passes, $failures;
    if ($got === $want) { $passes++; echo "ok - $name\n"; }
    else { $failures++; echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n"; }
  }
  function run($code) {
    global $user;
    $user = null;
    try {
      eval($code);
    } catch (Responded $r) {
      return $r->data;
    } catch (\Throwable $e) {
      return array('throwable' => get_class($e));
    }
    return null;
  }

  $csv = tempnam(sys_get_temp_dir(), 'zmcsv');
  file_put_contents($csv, "Door,rtsp://192.0.2.10/a\n");
  $_REQUEST = array('action' => 'import');
  $_FILES = array('import_file' => array('tmp_name' => $csv, 'error' => 0));

  // Import: the only existing monitor (id 7) is not editable by this user.
  \ZM\Monitor::$find_id = 7;
  $r = run($code);
  $streams = isset($r['Streams']) ? $r['Streams'] : array();
  check('a non-editable existing monitor is not offered', isset($streams[0]) ? $streams[0]['Monitor'] : 'missing', null);

  // Import: the existing monitor (id 3) is editable, so it is offered.
  \ZM\Monitor::$find_id = 3;
  $r = run($code);
  $streams = isset($r['Streams']) ? $r['Streams'] : array();
  $monitor = isset($streams[0]) ? $streams[0]['Monitor'] : null;
  check('an editable existing monitor is offered', $monitor !== null, true);

  unlink($csv);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
