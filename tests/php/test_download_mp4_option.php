<?php
// Structural test: the download modal offers mp4 (noArchive), tar and zip.
// ajax/event.php must pass noArchive through instead of turning it into zip,
// and only one radio may be pre-selected.
//
// Run: php tests/php/test_download_mp4_option.php

$root = dirname(__DIR__, 2);
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label\n";
  }
}

$ajax = file_get_contents($root.'/web/ajax/event.php');
$modal = file_get_contents($root.'/web/ajax/modals/download.php');

check("event.php accepts 'noArchive' as a download format",
  preg_match("/case 'download' :.*?in_array\(\\\$_REQUEST\['exportFormat'\], \['tar', 'zip', 'noArchive'\], true\)/s", $ajax) === 1);
preg_match_all('/<input type="radio"[^>]*name="exportFormat"[^>]*>/', $modal, $radios);
$checked = array_filter($radios[0], function($r) { return strpos($r, 'checked') !== false; });
check('the modal has three export format radios', count($radios[0]) === 3);
check('exactly one export format radio is pre-selected', count($checked) === 1);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
