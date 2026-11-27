<?php
// web/ajax/alarm.php must tolerate a request with no parameters: it has to
// reach ajaxError('Insufficient permissions'), not blow up on an undefined
// array key from $_REQUEST['id'] / $_REQUEST['command'].
//
// Run as: php tests/php/test_ajax_alarm_missing_params.php [repo_root]

namespace ZM {
  class Monitor {
    public function __construct($id) {}
    public function Id() { return 0; }
    public function canEdit() { return false; }
  }
}

namespace {
  class StopRequest extends Exception {}

  $root = $argv[1] ?? __DIR__.'/../..';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n";
    }
  }

  function validCardinal($v) { return (int)$v; }
  function validJsStr($v) { return (string)$v; }
  function getZmuCommand($args) { return 'true'; }
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  // alarm.php does require_once('includes/Monitor.php'); give it an empty
  // stub file in a temp dir first on the include path so the ZM\Monitor class
  // defined above is the one used.
  $tmp = sys_get_temp_dir().'/zm_alarm_test_'.getmypid();
  @mkdir($tmp.'/includes', 0777, true);
  file_put_contents($tmp.'/includes/Monitor.php', "<?php\n");
  set_include_path($tmp.PATH_SEPARATOR.get_include_path());

  // Undefined array keys surface as warnings/notices; turn them into errors so
  // the unfixed file fails this test loudly.
  set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
  }, E_WARNING | E_NOTICE);

  $_REQUEST = array();

  $msg = null;
  $error = null;
  try {
    include $root.'/web/ajax/alarm.php';
  } catch (StopRequest $e) {
    $msg = $e->getMessage();
  } catch (ErrorException $e) {
    $error = $e->getMessage();
  }

  check("no ErrorException about an undefined array key", is_null($error), true);
  check("request without parameters reaches ajaxError", $msg, 'Insufficient permissions');

  @unlink($tmp.'/includes/Monitor.php');
  @rmdir($tmp.'/includes');
  @rmdir($tmp);

  echo "\n".($failures ? "FAILURES: $failures\n" : "All passed\n");
  exit($failures ? 1 : 0);
}
