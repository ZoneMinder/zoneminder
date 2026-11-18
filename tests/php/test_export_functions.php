<?php
// Tests the page builders in web/skins/classic/includes/export_functions.php.
//
// - exportEventImages() printed $cspNonce, which is not global inside the
//   function, so a video event raised "Undefined variable $cspNonce" and the
//   warning ended up inside the exported HTML file.
// - exportEventImagesMaster() built its monitor list from an array that only
//   exists once an event was found, so an id list matching no event ended in
//   an array_flip() TypeError.
//
// Run as: php tests/php/test_export_functions.php   (from the repo root)

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
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

eval('namespace ZM; function Error($m) {} function Warning($m) {} function Debug($m) {}
class Event { public static function find($f = []) { return []; } }');

function translate($string) { return $string; }
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}
function dbFetchAll() { return []; }

define('ZM_PATH_WEB', __DIR__.'/../../web');
define('ZM_SKIN_PATH', 'skins/classic');
$css = 'base';

class FakeEvent {
  public function Id() { return 1; }
  public function Name() { return 'Event'; }
  public function Width() { return 640; }
  public function Height() { return 480; }
  public function DefaultVideo() { return '1-video.mp4'; }
}

require_once(__DIR__.'/../../web/skins/classic/includes/export_functions.php');

$warnings = array();
set_error_handler(function($no, $msg) use (&$warnings) { $warnings[] = $msg; return true; });
$html = exportEventImages(new FakeEvent(), false, false, array('1-capture.jpg' => '1-capture.jpg'));
restore_error_handler();
check('video event page raises no warning', $warnings, array());
check('video event page has the video element', strpos($html, '<video id="videoobj"') !== false, true);

$err = null;
ob_start();
try {
  $html = exportEventImagesMaster(array(99), false, false, 'flat');
} catch (Throwable $e) {
  $err = get_class($e);
}
while (ob_get_level()) ob_end_clean();
check('master page for ids matching no event does not throw', $err, null);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
