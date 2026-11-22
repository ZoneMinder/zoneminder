<?php
// web/ajax/events.php returned the monitor name (row Monitor) and the storage
// name (row Storage) unescaped while the list script inserts them as HTML
// (stored XSS through a monitor or storage name); they are now escaped like
// Name and Cause.
//
// Run: php tests/php/test_events_row_names_escaped.php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
$src = file_get_contents($root.'/web/ajax/events.php');
if ($src === false) {
  echo "FAIL could not read events.php\n";
  exit(1);
}

$stmts = '';
if (preg_match_all('/^\s*\$row\[\'(?:Monitor|Storage)\'\]\s*=.*;$/m', $src, $m)) {
  $stmts = implode("\n", $m[0]);
}

function validHtmlStr($s) {
  return htmlspecialchars((string)$s);
}

$StorageById = array(3 => new class {
  function Name() {
    return '<b>disk&1';
  }
});
$row = array('Monitor' => '<img src=x onerror=alert(1)>', 'StorageId' => 3);
eval($stmts);

$failures = 0;
function check($name, $got, $ok) {
  global $failures;
  echo ($ok ? 'PASS' : 'FAIL')." $name: ".var_export($got, true)."\n";
  if (!$ok) $failures++;
}

check('Monitor is escaped', $row['Monitor'],
  $row['Monitor'] === '&lt;img src=x onerror=alert(1)&gt;');
check('Storage is escaped', $row['Storage'],
  $row['Storage'] === '&lt;b&gt;disk&amp;1');
exit($failures ? 1 : 0);
