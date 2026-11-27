<?php
// ajax/stats.php with raw: Zones.Area is in the 0-10000 percentage coordinate space,
// but the raw branch divided pixel counts by it as if it were a pixel area, so a
// full-frame zone showed 100*n/10000 percent instead of n over the frame's pixels.
//
// Run as: php tests/php/test_ajax_stats_raw_percent.php

class Done extends Exception {}
// stats.php checks the user may view the event; this one may.
$stubdir = sys_get_temp_dir().'/zm_stats_raw_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Event.php', "<?php\nnamespace ZM; class Event { public function __construct(\$id) {} public function Id() { return 5; } public function canView() { return true; } }\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
register_shutdown_function(function() use ($stubdir) { @unlink($stubdir.'/includes/Event.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir); });
function validCardinal($v) { return preg_replace('/\D/', '', $v); }
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
