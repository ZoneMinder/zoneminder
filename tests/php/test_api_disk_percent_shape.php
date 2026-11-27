<?php
// Source check for HostController::getDiskPercent() shape consistency.
// Verifies that (a) single-monitor branch returns structured array not raw shell_exec,
// (b) per-monitor directory uses storage path via Storage()->Path() with fallback,
// (c) both branches call the same private helper.

$file = __DIR__.'/../../web/api/app/Controller/HostController.php';
$code = file_get_contents($file);
if ($code === false) {
    fwrite(STDERR, "Failed to read $file\n");
    exit(1);
}

$passes = 0;
$failures = 0;

function check($label, $condition) {
    global $passes, $failures;
    if ($condition) {
        $passes++;
        echo "ok - $label\n";
    } else {
        $failures++;
        echo "not ok - $label\n";
    }
}

// (a) Single-monitor branch no longer assigns raw shell_exec output to $usage
// Old pattern: $usage = shell_exec("du -s0 ...");
// New pattern: $space = $this->_getMonitorDiskUsage(...); $usage = array($name => array('space' => $space, 'color' => $color));
$singleMonitorRawAssign = (bool)preg_match(
    '/if\s*\(\s*\$mid\s*\)\s*\{[^}]*\$usage\s*=\s*shell_exec\s*\(/s',
    $code
);
check('single-monitor branch does not assign raw shell_exec to $usage', !$singleMonitorRawAssign);

// (b) Per-monitor directory comes from storage path (Storage()->Path() or helper using it)
// Check that _getMonitorDiskUsage uses Storage()->Path() with fallback to $zm_dir_events
$helperUsesStoragePath = (bool)preg_match(
    '/_getMonitorDiskUsage\s*\([^)]*\)\s*\{[^}]*\$storage\s*=\s*\$zm_monitor->Storage\(\)\s*;\s*\$storagePath\s*=\s*\$storage->Path\(\)\s*;\s*\$basePath\s*=\s*\$storagePath\s*\?\s*\$storagePath\s*:\s*\$zm_dir_events/s',
    $code
);
check('helper uses Storage()->Path() with fallback to $zm_dir_events', $helperUsesStoragePath);

// (c) Both branches call the same helper
$singleCallsHelper = (bool)preg_match(
    '/if\s*\(\s*\$mid\s*\)\s*\{[^}]*\$this->_getMonitorDiskUsage\s*\(/s',
    $code
);
$multiCallsHelper = (bool)preg_match(
    '/else\s*\{[^}]*foreach\s*\([^}]*\$this->_getMonitorDiskUsage\s*\(/s',
    $code
);
check('single-monitor branch calls _getMonitorDiskUsage helper', $singleCallsHelper);
check('all-monitors branch calls _getMonitorDiskUsage helper', $multiCallsHelper);

// Additional structural checks
// Helper exists and is private
$helperExists = (bool)preg_match(
    '/private\s+function\s+_getMonitorDiskUsage\s*\(/s',
    $code
);
check('private helper _getMonitorDiskUsage exists', $helperExists);

// Helper converts KB to GB (divides by 1024/1024)
$helperConvertsToGB = (bool)preg_match(
    '/\$space\s*=\s*\$space\/1024\/1024\s*;/s',
    $code
);
check('helper converts KB to GB', $helperConvertsToGB);

// Helper returns rtrim'd space value
$helperReturnsRtrim = (bool)preg_match(
    '/return\s+rtrim\s*\(\s*\$space\s*\)\s*;/s',
    $code
);
check('helper returns rtrimmed space value', $helperReturnsRtrim);

// Single-monitor branch returns array keyed by monitor name with space+color
$singleReturnsStructured = (bool)preg_match(
    '/\$usage\s*=\s*array\s*\(\s*\$name\s*=>\s*array\s*\(\s*[\'"\s]*space[\'"\s]*\s*=>\s*\$space\s*,\s*[\'"\s]*color[\'"\s]*\s*=>\s*\$color\s*\)\s*\)\s*;/s',
    $code
);
check('single-monitor branch returns structured array with space+color', $singleReturnsStructured);

// Escapeshellarg preserved in helper
$helperUsesEscapeshellarg = (bool)preg_match(
    '/escapeshellarg\s*\(\s*\$path\s*\)/',
    $code
);
check('helper uses escapeshellarg', $helperUsesEscapeshellarg);

// Total entry unchanged in all-monitors branch
$totalEntryUnchanged = (bool)preg_match(
    '/\$usage\[\s*[\'"]Total[\'"]\s*\]\s*=\s*array\s*\(\s*[\'"\s]*space[\'"\s]*\s*=>\s*rtrim\s*\(\s*\$space\s*\)\s*,\s*[\'"\s]*color[\'"\s]*\s*=>\s*[\'"]#F7464A[\'"]/s',
    $code
);
check('Total entry unchanged in all-monitors branch', $totalEntryUnchanged);

// All-monitors find() includes StorageId so helper can resolve storage path
$multiFindIncludesStorageId = (bool)preg_match(
    "/fields'\s*=>\s*array\s*\(.*?StorageId/s",
    $code
);
check('all-monitors find() fields include StorageId', $multiFindIncludesStorageId);

echo "\n";
$total = $passes + $failures;
echo "$total checks: $passes passed, $failures failed\n";
exit($failures ? 1 : 0);