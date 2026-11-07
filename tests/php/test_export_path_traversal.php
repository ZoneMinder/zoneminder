<?php
// Regression test for the export-generation path traversal siblings of
// GHSA-fvx4-86xp-9v22:
//   - GHSA-9hf5-jpww-rvpp: exportEvents() ($export_root, web/skins/classic/
//     includes/export_functions.php) built ZM_DIR_EXPORTS/<root>; the regex
//     kept dots so '..' climbed to the parent, which was chmod'd/written/tarred.
//   - GHSA-72h2-rvhv-wr5g: downloadEvents() ($export_root, web/includes/
//     download_functions.php) built DIR_EXPORTS_DOWNLOAD/<root> the same way.
//
// Pure source checkout, no DB/filesystem:  php tests/php/test_export_path_traversal.php
// Part B is skipped on branches without download_functions.php (e.g. 1.36).

$root = __DIR__.'/../..';
$failures = 0; $passes = 0;
function ok($c,$n){ global $failures,$passes; if($c){$passes++;echo "ok - $n\n";} else {$failures++;echo "not ok - $n\n";} }

// ---- Part A: exportEvents() refuses a traversal $export_root (all branches) ----
$exp = file_get_contents($root.'/web/skins/classic/includes/export_functions.php');

$sanitizePos = strpos($exp, "\$export_root = preg_replace('/[^\\w\\-.]/', '', \$export_root);");
$buildPos    = strpos($exp, "\$export_dir = ZM_DIR_EXPORTS.'/'.\$export_root");
ok($sanitizePos !== false, 'export_functions: found the export_root sanitize');
ok($buildPos !== false, 'export_functions: found the export_dir construction');

// The guard must sit between the sanitize and the dir build, and reject '.'/'..'.
$between = ($sanitizePos !== false && $buildPos !== false && $buildPos > $sanitizePos)
  ? substr($exp, $sanitizePos, $buildPos - $sanitizePos) : '';
ok(strpos($between, "\$export_root === '..'") !== false
   && strpos($between, "\$export_root === '.'") !== false,
   'export_functions: a traversal export_root is refused before the path is built');

// Behavioural: lift the exact guard statement and run it over crafted values.
if (preg_match('/^.*if \(empty\(\$export_root\).*\$export_root = \'zmExport\';.*$/m', $exp, $m)) {
  $guard = $m[0];
  $run = function($v) use ($guard) { $export_root = $v; eval($guard); return $export_root; };
  ok($run('..') === 'zmExport', "export guard: '..' -> default");
  ok($run('.')  === 'zmExport', "export guard: '.' -> default");
  ok($run('')   === 'zmExport', "export guard: '' -> default");
  ok($run('zmExport_123') === 'zmExport_123', 'export guard: a normal name is kept');
  ok($run('My.Export') === 'My.Export', 'export guard: a legitimate dotted name is kept');
} else {
  ok(false, 'export_functions: could not locate the guard statement to exercise');
}

// ---- Part B: downloadEvents() + safeExportComponent() (master/1.38) ----
$dlFile = $root.'/web/includes/download_functions.php';
if (is_file($dlFile)) {
  $dl = file_get_contents($dlFile);

  // downloadEvents applies the guard before building its export_dir.
  $scPos = strpos($dl, 'safeExportComponent($export_root)');
  $ddPos = strpos($dl, "\$export_dir = DIR_EXPORTS_DOWNLOAD.'/'.\$export_root;");
  ok($scPos !== false && $ddPos !== false && $scPos < $ddPos,
     'download_functions: export_root passes safeExportComponent before the path is built');

  // Behavioural: lift safeExportComponent() and check its policy.
  if (preg_match('/function safeExportComponent\(\$value\)\s*\{.*?\n\}/s', $dl, $m)) {
    eval($m[0]);
    ok(safeExportComponent('..') === '', "safeExportComponent: '..' rejected");
    ok(safeExportComponent('.')  === '', "safeExportComponent: '.' rejected");
    ok(safeExportComponent('')   === '', "safeExportComponent: '' rejected");
    ok(safeExportComponent('a/b') === '', 'safeExportComponent: a separator is rejected');
    ok(safeExportComponent("a\0b") === '', 'safeExportComponent: a null byte is rejected');
    ok(safeExportComponent('Export123456') === 'Export123456', 'safeExportComponent: a normal name is kept');
    ok(safeExportComponent('My.Export') === 'My.Export', 'safeExportComponent: a legitimate dotted name is kept');
  } else {
    ok(false, 'download_functions: could not locate safeExportComponent to exercise');
  }
} else {
  echo "# download_functions.php absent on this branch; Part B skipped (72h2 N/A)\n";
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
