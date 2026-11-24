<?php
// Security regression test: web/ajax/monitor.php must not build a monitor from an
// array supplied as "mid".
//
// ZM_Object::__construct() treats an array as the object's row, so a request such as
// mid[Id]=../../etc/x could hand the ajax endpoint an attacker-chosen Id (and any other
// column) with the row trusted. validCardinal() has to reduce mid to a scalar digit
// string before it reaches `new ZM\Monitor($mid)`.
//
// Run as: php tests/php/test_ajax_monitor_forged_mid.php
// Optionally: ZM_MONITOR_AJAX=/path/to/web/ajax/monitor.php php tests/php/test_ajax_monitor_forged_mid.php

namespace ZM {
  class Monitor {
    public static $built = array();
    private $row;

    public function __construct($IdOrRow = null) {
      self::$built[] = $IdOrRow;
      if (is_array($IdOrRow)) {
        $this->row = $IdOrRow;
      } elseif ($IdOrRow === '5' || $IdOrRow === '7' || $IdOrRow === 5 || $IdOrRow === 7) {
        $this->row = array('Id' => (string)$IdOrRow);
      } else {
        $this->row = array();
      }
    }

    public function Id() {
      return isset($this->row['Id']) ? $this->row['Id'] : null;
    }

    public function canView() { return true; }

    public function getDefaults() {
      return array('Name' => array('filter_regexp' => '/[^A-Za-z0-9 ]/'));
    }

    public function requestAudioLevel() {}
    public function audioLevel() { return 0; }
    public function audioAlarm() { return false; }
    public function AudioThreshold() { return 1; }
    public function AudioDetection() { return false; }
    public function OnvifStatus() { return 0; }
  }
}

namespace {

class AjaxDone extends Exception {}

function ajaxError($m) { throw new AjaxDone('error:'.$m); }
function ajaxResponse($a) { throw new AjaxDone('ok'); }

function translate($s) { return $s; }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }

function validCardinal($input) {
  if (!is_scalar($input)) return '';
  return preg_replace('/\D/', '', $input);
}

function canView($area, $mid = false) {
  if ($mid === false) {
    return (bool)$GLOBALS['viewMonitors'];
  }
  return is_scalar($mid) && in_array((string)$mid, array('5', '7'), true);
}

$GLOBALS['viewMonitors'] = false;
$GLOBALS['user'] = new class {
  public function Username() { return 'tester'; }
};

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function starts_with($haystack, $needle) {
  return strpos((string)$haystack, $needle) === 0;
}

// The ajax file does require_once('includes/Monitor.php'). Run from a scratch dir that
// has an empty includes/Monitor.php so the require succeeds; the class above is the one
// that gets used.
$scratch = sys_get_temp_dir().'/zm_test_ajax_monitor_'.getmypid();
if (!is_dir($scratch.'/includes')) {
  @mkdir($scratch.'/includes', 0777, true);
}
file_put_contents($scratch.'/includes/Monitor.php', "<?php\n");
chdir($scratch);

$ajax = getenv('ZM_MONITOR_AJAX');
if (!$ajax || !is_file($ajax)) {
  $ajax = __DIR__.'/../../web/ajax/monitor.php';
}

function run($request, $viewMonitors) {
  global $ajax, $user;
  $_REQUEST = $request;
  \ZM\Monitor::$built = array();
  $GLOBALS['viewMonitors'] = $viewMonitors;
  try {
    include $ajax;
  } catch (AjaxDone $e) {
    return $e->getMessage();
  }
  return '';
}

$r = run(array('mid' => '5', 'action' => 'onvifStatus'), false);
check('scalar mid 5 the user can view answers ok', $r, 'ok');

$r = run(array('mid' => array('Id' => '../../etc/x'), 'action' => 'onvifStatus'), true);
check('array mid is rejected even with general Monitors view', starts_with($r, 'error:'), true);
check('no monitor is built from an array mid', \ZM\Monitor::$built, array());

$r = run(array('mid' => array('Id' => '5'), 'action' => 'audioLevel'), false);
check('array mid with a viewer-shaped Id is rejected', starts_with($r, 'error:'), true);
check('no monitor is built from a forged array mid', \ZM\Monitor::$built, array());

$r = run(array('mid' => '5x', 'action' => 'onvifStatus'), true);
check('non-digit mid is reduced to a scalar digit string', $r, 'ok');
check('the reduced mid 5 builds the monitor', \ZM\Monitor::$built, array('5'));

$r = run(array('mid' => '9', 'action' => 'onvifStatus'), false);
check('a mid the user cannot view is rejected', starts_with($r, 'error:'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);

}
