<?php
// ajax/stats.php with raw: Zones.Area is in the 0-10000 percentage coordinate space,
// but the raw branch divided pixel counts by it as if it were a pixel area, so a
// full-frame zone showed 100*n/10000 percent instead of n over the frame's pixels.
//
// Run as: php tests/php/test_ajax_stats_raw_percent.php

class Done extends Exception {}
function canView($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = "error: $m"; throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = $r; throw new Done(); }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }
function dbFetchAll($sql, $col = null, $args = array()) {
  // 640x480 event, one full-frame zone (Area 10000), 3072 alarm pixels = 1.0% of the frame
  return array(array(
    'Width' => 640, 'Height' => 480, 'ZoneName' => 'All', 'Units' => 'Percent', 'Area' => 10000,
    'PixelDiff' => 20, 'AlarmPixels' => 3072, 'FilterPixels' => 3072, 'BlobPixels' => 3072,
    'Blobs' => 1, 'MinBlobSize' => 3072, 'MaxBlobSize' => 3072,
    'MinX' => 0, 'MinY' => 0, 'MaxX' => 10, 'MaxY' => 10,
  ));
}

$_REQUEST = array('eid' => '5', 'fid' => '7', 'raw' => 'true');
try {
  include __DIR__.'/../../web/ajax/stats.php';
} catch (Done $e) {
}
$row = is_array($GLOBALS['out']) ? $GLOBALS['out']['raw'][0] : array();

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}
check('alarm pixels are a share of the frame pixels', $row['AlarmPixels'] ?? null, '3072 (1.0%)');
check('single blob size is a share of the frame pixels', $row['BlobSizes'] ?? null, '3072 (1.0%)');
exit($failures ? 1 : 0);
