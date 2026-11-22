<?php
// A zone deleted after the event leaves Stats rows whose joined ZoneName is NULL.
// The frame stats table and the raw ajax response showed a blank Zone cell for it.
//
// Run as: php tests/php/test_stats_deleted_zone_name.php [repo root]
$root = $argv[1] ?? dirname(__DIR__, 2);

eval('namespace ZM { function Warning($m) {} function Error($m) {} function Info($m) {} function Debug($m) {} }');
class Done extends Exception {}
function canView($a) { return true; }
function ajaxError($m) { throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = $r; throw new Done(); }
function i18n() { return 'en-US'; }
function translate($s) { return $s; }
function validHtmlStr($s) { return is_null($s) ? '' : htmlspecialchars((string)$s, ENT_QUOTES); }
function dbFetchAll($sql, $col = null, $args = array()) {
  return array(array(
    'EventId' => 5, 'FrameId' => 7, 'ZoneId' => 3, 'Width' => 640, 'Height' => 480,
    'ZoneName' => null, 'Units' => null, 'Area' => null, 'Coords' => null,
    'PixelDiff' => 20, 'AlarmPixels' => 100, 'FilterPixels' => 100, 'BlobPixels' => 100,
    'Blobs' => 1, 'MinBlobSize' => 100, 'MaxBlobSize' => 100, 'Score' => 4,
    'MinX' => 0, 'MinY' => 0, 'MaxX' => 10, 'MaxY' => 10,
  ));
}

$failures = 0;
function check($n, $ok) { global $failures; if ($ok) echo "ok - $n\n"; else { $failures++; echo "FAIL - $n\n"; } }

$src = file_get_contents($root.'/web/skins/classic/includes/functions.php');
preg_match('/function getStatsTableHTML\(.*?\n\}\n/s', $src, $m);
eval($m[0]);
$html = getStatsTableHTML(5, 7);
check('table labels a deleted zone by id', strpos($html, '<td class="colZone">Zone 3</td>') !== false);

$_REQUEST = array('eid' => '5', 'fid' => '7', 'raw' => 'true');
try { include $root.'/web/ajax/stats.php'; } catch (Done $e) {}
check('raw response labels a deleted zone by id', ($GLOBALS['out']['raw'][0]['ZoneName'] ?? null) === 'Zone 3');
exit($failures ? 1 : 0);
