<?php
// A monitor with no events has NULL in Event_Summaries (or no row at all), and
// the console must show 0 bytes for it rather than the text 'null'.
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
    echo '    got:  '.var_export($got, true)."\n";
    echo '    want: '.var_export($want, true)."\n";
  }
}

$fn = file_get_contents(__DIR__.'/../../web/includes/functions.php');
if (!preg_match('/^function human_filesize\(.*?\n\}/ms', $fn, $m)) {
  echo "FAIL could not find human_filesize()\n";
  exit(1);
}
eval($m[0]);

$path = __DIR__.'/../../web/ajax/console.php';
$src = file_get_contents($path);
if (!preg_match('/^function monitorEventDiskSpace\(.*?\n\}/ms', $src, $m)) {
  echo "FAIL could not find monitorEventDiskSpace() in $path\n";
  exit(1);
}
eval($m[0]);

check('null disk space shows zero', monitorEventDiskSpace(array('TotalEventDiskSpace'=>null), 'Total'), '0.00B ');
check('missing summary row shows zero', monitorEventDiskSpace(array(), 'Hour'), '0.00B ');
check('real value is formatted', monitorEventDiskSpace(array('DayEventDiskSpace'=>2048), 'Day'), '2.00kB');

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
