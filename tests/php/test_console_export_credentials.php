<?php

namespace ZM {
  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function to_json() { return json_encode($this->row); }
  }

  class Zone {
    public static function find($filter = array()) { return array(); }
  }

  class Group {
    public static function find() { return array(); }
  }

  class Group_Monitor {
    public static function find() { return array(); }
  }
}

namespace {
  ob_start();
  $failures = 0;
  $passes = 0;

  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "  ok $name\n";
    } else {
      $failures++;
      echo "  FAIL $name\n";
      echo '    got:  '.var_export($got, true)."\n";
      echo '    want: '.var_export($want, true)."\n";
    }
  }

  define('ZM_VERSION', '1.37.0');

  function canView($area) { return true; }
  function visibleMonitor($id) { return true; }
  function editableMonitor($id) { return $id == 1; }
  function ajaxError($msg) {}
  function dbFetchAll($sql) {
    $row = array(
      'Host' => 'cam1',
      'Path' => 'rtsp://admin:s3cret@10.0.0.5/live',
      'SecondPath' => 'rtsp://a:b@h/x',
      'User' => 'admin',
      'Pass' => 's3cret',
      'ONVIF_Username' => 'u',
      'ONVIF_Password' => 'p',
      'ControlAddress' => 'user:pw@192.168.1.5:80',
      'ONVIF_URL' => 'http://o:q@10.0.0.9/onvif',
      'Deleted' => 0,
      'Sequence' => 1,
    );
    return array(
      array('Id' => 1, 'Name' => 'Cam1') + $row,
      array('Id' => 2, 'Name' => 'Cam2', 'Host' => 'u:p@cam2') + $row,
    );
  }

  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $path = $root.'/web/ajax/console.php';
  $src = file_get_contents($path);
  // The strip helper uses the real ZM\removeUserinfo().
  require_once($root.'/web/includes/log_redact.php');

  $has_strip = preg_match('/^function exportStripCredentials\(.*?\n\}/ms', $src, $m);
  if ($has_strip) {
    eval($m[0]);
    $in = array(
      'Name' => 'Front',
      'Host' => 'u:p@cam',
      'Path' => 'rtsp://admin:s3cret@10.0.0.5/live',
      'SecondPath' => 'rtsp://a:b@h/x',
      'User' => 'admin',
      'Pass' => 's3cret',
      'ONVIF_Username' => 'u',
      'ONVIF_Password' => 'p',
      'ControlAddress' => 'user:pw@192.168.1.5:80',
      'ONVIF_URL' => 'http://o:q@10.0.0.9/onvif',
    );
    $out = exportStripCredentials($in);
    check('strip removes User', array_key_exists('User', $out), false);
    check('strip removes Pass', array_key_exists('Pass', $out), false);
    check('strip removes ONVIF_Username', array_key_exists('ONVIF_Username', $out), false);
    check('strip removes ONVIF_Password', array_key_exists('ONVIF_Password', $out), false);
    check('strip removes Host userinfo', $out['Host'], 'cam');
    check('strip removes Path userinfo', $out['Path'], 'rtsp://10.0.0.5/live');
    check('strip removes SecondPath userinfo', $out['SecondPath'], 'rtsp://h/x');
    check('strip removes ControlAddress userinfo', $out['ControlAddress'], '192.168.1.5:80');
    check('strip removes ONVIF_URL userinfo', $out['ONVIF_URL'], 'http://10.0.0.9/onvif');
    check('strip keeps Name', $out['Name'], 'Front');
    $plain = exportStripCredentials(array('ControlAddress' => '192.168.1.5:80'));
    check('strip leaves ControlAddress without userinfo unchanged', $plain['ControlAddress'], '192.168.1.5:80');
    $scheme = exportStripCredentials(array('ControlAddress' => 'http://admin:pw@10.0.0.1:80'));
    check('strip removes ControlAddress userinfo after a scheme', $scheme['ControlAddress'], 'http://10.0.0.1:80');
  }

  if (!preg_match('/^function exportMonitorsJSON\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find exportMonitorsJSON() in $path\n";
    exit(1);
  }
  $fn = $m[0];
  $fn = str_replace('exit();', 'return;', $fn);
  $fn = str_replace("require_once('includes/Monitor.php');", '', $fn);
  $fn = str_replace("require_once('includes/Zone.php');", '', $fn);
  $fn = str_replace("require_once('includes/Group.php');", '', $fn);
  $fn = str_replace("require_once('includes/Group_Monitor.php');", '', $fn);
  eval($fn);

  ob_start();
  exportMonitorsJSON();
  $json = ob_get_clean();
  $export = json_decode($json, true);

  $m1 = $export['monitors'][0];
  $m2 = $export['monitors'][1];

  check('editable monitor keeps User', $m1['User'], 'admin');
  check('editable monitor keeps Pass', $m1['Pass'], 's3cret');
  check('editable monitor keeps full Path', $m1['Path'], 'rtsp://admin:s3cret@10.0.0.5/live');
  check('editable monitor keeps ControlAddress', $m1['ControlAddress'], 'user:pw@192.168.1.5:80');
  check('editable monitor keeps ONVIF_URL', $m1['ONVIF_URL'], 'http://o:q@10.0.0.9/onvif');
  check('non-editable monitor has no User', array_key_exists('User', $m2), false);
  check('non-editable monitor has no Pass', array_key_exists('Pass', $m2), false);
  check('non-editable monitor has no ONVIF_Username', array_key_exists('ONVIF_Username', $m2), false);
  check('non-editable monitor has no ONVIF_Password', array_key_exists('ONVIF_Password', $m2), false);
  check('non-editable monitor Path stripped', $m2['Path'], 'rtsp://10.0.0.5/live');
  check('non-editable monitor SecondPath stripped', $m2['SecondPath'], 'rtsp://h/x');
  check('non-editable monitor Host stripped', $m2['Host'], 'cam2');
  check('non-editable monitor ControlAddress stripped', $m2['ControlAddress'], '192.168.1.5:80');
  check('non-editable monitor ONVIF_URL stripped', $m2['ONVIF_URL'], 'http://10.0.0.9/onvif');
  check('non-editable monitor keeps Name', $m2['Name'], 'Cam2');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
