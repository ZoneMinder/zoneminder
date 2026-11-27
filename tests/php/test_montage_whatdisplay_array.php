<?php
// The montage view picks the "Show" option from the whatDisplay request value by looking it up
// as a key in the list of options. isset() with an array as the key throws a TypeError, so
// ?view=montage&whatDisplay[]=x was a fatal error when the audio analyzer is installed. A value
// that is not a string must be ignored like an unknown one. (The zmWhatDisplay cookie is not
// covered: Monitor::getStreamHTML reads it unchecked as well.)
//
// Run as: php tests/php/test_montage_whatdisplay_array.php

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

function validHtmlStr($input) {
  if (is_null($input) or is_array($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}
function translate($s) { return $s; }
define('AUDIO_MOTION_ENABLED', true);

$source = file_get_contents(__DIR__.'/../../web/skins/classic/views/montage.php');
$start = strpos($source, '$whatDisplay = [');
$endMarker = "\$whatDisplaySelected = 'OnlyVideo';\n            }\n";
$end = strpos($source, $endMarker, (int)$start);
if ($start === false or $end === false) {
  echo "FAIL - could not find the whatDisplay block in montage.php\n";
  exit(1);
}
$block = substr($source, $start, $end + strlen($endMarker) - $start);

function selected($request, $cookie, $block) {
  $_REQUEST = $request;
  $_COOKIE = $cookie;
  try {
    eval($block);
    return $whatDisplaySelected;
  } catch (Throwable $e) {
    return get_class($e);
  }
}

check('no value gives Default', selected(array(), array(), $block), 'Default');
check('a request value is used', selected(array('whatDisplay' => 'OnlyVideo'), array(), $block), 'OnlyVideo');
check('a cookie value is used', selected(array(), array('zmWhatDisplay' => 'OnlyAudioVisualization'), $block), 'OnlyAudioVisualization');
check('an unknown request value is ignored', selected(array('whatDisplay' => 'nope'), array(), $block), 'Default');
check('an array request value is ignored', selected(array('whatDisplay' => array('x')), array(), $block), 'Default');
check('an array request value leaves the cookie', selected(array('whatDisplay' => array('x')), array('zmWhatDisplay' => 'OnlyVideo'), $block), 'OnlyVideo');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
