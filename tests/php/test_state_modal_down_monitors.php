<?php
/*
 * The Run State modal only said whether ZoneMinder was running or stopped, so
 * a user could not tell which cameras were down. When ZoneMinder is running it
 * now lists the monitors the user can view that are enabled and capturing but
 * that the console would mark down: not running, or capturing all the time with
 * no capture fps.
 *
 * Run: php tests/php/test_state_modal_down_monitors.php [root]
 */
namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $modalFile = $root.'/web/ajax/modals/state.php';

  // A monitor the user may not view.
  $hiddenId = 14;
  $monitors = array(
    array(
      'Id' => 10, 'Name' => 'Front Door & <Gate>', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'Always', 'Deleted' => '0',
      'Status' => 'NotRunning', 'CaptureFPS' => '0.00',
    ),
    array(
      'Id' => 11, 'Name' => 'Back Yard', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'Always', 'Deleted' => '0',
      'Status' => 'Connected', 'CaptureFPS' => '10.00',
    ),
    // The process is up but the camera has not connected yet.
    array(
      'Id' => 12, 'Name' => 'Side Gate', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'Always', 'Deleted' => '0',
      'Status' => 'Running', 'CaptureFPS' => '0.00',
    ),
    array(
      'Id' => 13, 'Name' => 'Disabled Cam', 'Type' => 'Ffmpeg',
      'Enabled' => '0', 'Capturing' => 'Always', 'Deleted' => '0',
      'Status' => 'NotRunning', 'CaptureFPS' => '0.00',
    ),
    array(
      'Id' => 15, 'Name' => 'No Capture Cam', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'None', 'Deleted' => '0',
      'Status' => 'NotRunning', 'CaptureFPS' => '0.00',
    ),
    // On demand with nobody watching: running, no fps, and that is normal.
    array(
      'Id' => 16, 'Name' => 'Ondemand Cam', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'Ondemand', 'Deleted' => '0',
      'Status' => 'Running', 'CaptureFPS' => '0.00',
    ),
    array(
      'Id' => 14, 'Name' => 'Hidden Cam', 'Type' => 'Ffmpeg',
      'Enabled' => '1', 'Capturing' => 'Always', 'Deleted' => '0',
      'Status' => 'NotRunning', 'CaptureFPS' => '0.00',
    ),
  );

  function canEdit($a, $mid = null) { return true; }
  function canView($a, $mid = null) {
    global $hiddenId;
    if ($a === 'Monitors' && $mid) return $mid != $hiddenId;
    return true;
  }
  function daemonCheck() { return true; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validCardinal($s) { return preg_replace('/\D/', '', (string)$s); }
  function getCSRFinputHTML() { return ''; }
  function dbFetchAll($sql, $col = null, $params = array()) {
    global $monitors;
    if (strpos($sql, 'FROM States') !== false) return array();
    return $monitors;
  }

  $failures = 0;
  function check($label, $cond) {
    global $failures;
    if ($cond) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label\n";
    }
  }

  ob_start();
  include $modalFile;
  $html = ob_get_clean();

  check('a monitor with no camera connection is listed',
    strpos($html, 'Front Door &amp; &lt;Gate&gt;') !== false);
  check('the reason is shown', strpos($html, 'Not Running') !== false);
  check('a running monitor with no capture fps is listed',
    strpos($html, 'Side Gate') !== false && strpos($html, 'No capture FPS') !== false);
  check('an on-demand monitor with no viewer is not listed', strpos($html, 'Ondemand Cam') === false);
  check('a connected monitor is not listed', strpos($html, 'Back Yard') === false);
  check('a disabled monitor is not listed', strpos($html, 'Disabled Cam') === false);
  check('a non-capturing monitor is not listed', strpos($html, 'No Capture Cam') === false);
  check('a monitor the user may not view is not listed', strpos($html, 'Hidden Cam') === false);
  check('the name is escaped once',
    strpos($html, 'Front Door &amp;amp;') === false);

  echo "\n".($failures ? "$failures failure(s)\n" : "all passed\n");
  exit($failures ? 1 : 0);
}
