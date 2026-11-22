<?php
/*
 * Test getStatsTableHTML percentage calculations.
 *
 * Run: php tests/php/test_stats_table_percent.php
 * Run against original: php tests/php/test_stats_table_percent.php /tmp/numes-tests/stats-orig
 */
$repoRoot = $argv[1] ?? dirname(__DIR__, 2);
$funcFile = $repoRoot . '/web/skins/classic/includes/functions.php';

$src = file_get_contents($funcFile);
if (!$src) {
    echo "FAIL - Cannot read $funcFile\n";
    exit(1);
}

$pattern = '/function getStatsTableHTML\(.*?\n\}\n/s';
if (!preg_match($pattern, $src, $m)) {
    echo "FAIL - Could not extract getStatsTableHTML function\n";
    exit(1);
}
$funcCode = $m[0];

$tests = 0;
$failures = 0;
function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}

eval('namespace ZM {
    function Warning($m) {} function Error($m) { $GLOBALS["errs"][] = $m; } function Info($m) {} function Debug($m) {}
}');

function i18n() { return "en-US"; }
function canView($x) { return true; }
function translate($s) { return $s; }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }

$capturedSql = null;
function dbFetchAll($sql, $col, $params) {
    global $capturedSql, $testRows;
    $capturedSql = $sql;
    return $testRows;
}

eval($funcCode);

echo "Case a: deleted zone (Area=null)\n";
$testRows = [[
    'EventId' => 1, 'FrameId' => 1, 'ZoneId' => 1,
    'PixelDiff' => 100, 'AlarmPixels' => 12000, 'FilterPixels' => 6000, 'BlobPixels' => 3000,
    'Blobs' => 1, 'MinBlobSize' => 3000, 'MaxBlobSize' => 3000, 'Score' => 50,
    'MinX' => 10, 'MinY' => 10, 'MaxX' => 100, 'MaxY' => 100,
    'ZoneName' => null, 'Units' => 'Pixels', 'Area' => null, 'Coords' => '',
    'Width' => 640, 'Height' => 480, 'MonitorName' => 'Mon1'
]];
$out = getStatsTableHTML(1, 1);
check('output contains plain 12000', strpos($out, '>12000<') !== false || strpos($out, '>12000 ') !== false || preg_match('/>12000\b/', $out));
check('output does NOT contain 1200000%', strpos($out, '1200000%') === false);
check('output does NOT contain percentage for AlarmPixels', strpos($out, '12000 (') === false || strpos($out, '12000 (100%)') !== false);
check('output contains plain 6000 for FilterPixels', strpos($out, '>6000<') !== false || strpos($out, '>6000 ') !== false || preg_match('/>6000\b/', $out));
check('output contains plain 3000 for BlobPixels', strpos($out, '>3000<') !== false || strpos($out, '>3000 ') !== false || preg_match('/>3000\b/', $out));

echo "Case b: full frame zone (Area=10000)\n";
$testRows = [[
    'EventId' => 1, 'FrameId' => 1, 'ZoneId' => 1,
    'PixelDiff' => 100, 'AlarmPixels' => 3072, 'FilterPixels' => 1536, 'BlobPixels' => 768,
    'Blobs' => 1, 'MinBlobSize' => 768, 'MaxBlobSize' => 768, 'Score' => 50,
    'MinX' => 10, 'MinY' => 10, 'MaxX' => 100, 'MaxY' => 100,
    'ZoneName' => 'Zone1', 'Units' => 'Pixels', 'Area' => 10000, 'Coords' => '',
    'Width' => 640, 'Height' => 480, 'MonitorName' => 'Mon1'
]];
$out = getStatsTableHTML(1, 1);
check('output contains 3072 (1%) for AlarmPixels', strpos($out, '3072 (1%)') !== false);
check('output contains 1536 (0%) or 1536 (1%) for FilterPixels', strpos($out, '1536 (') !== false);
check('output contains 768 (0%) or 768 (1%) for BlobPixels', strpos($out, '768 (') !== false);

echo "Case c: SQL does not select M.Width / M.Height\n";
check('SQL does not contain M.Width', stripos($capturedSql, 'M.Width') === false);
check('SQL does not contain M.Height', stripos($capturedSql, 'M.Height') === false);
check('SQL selects E.* (includes event Width/Height)', stripos($capturedSql, 'E.*') !== false);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);