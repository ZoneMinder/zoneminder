<?php
// The Server modal in Options > Servers and the monitor edit page limited the Port
// field to 65536, one more than the highest TCP port, so the browser accepted a
// port that no server can listen on.
//
// Run: php tests/php/test_server_modal_port.php   (from the repo root)

$src = file_get_contents(__DIR__.'/../../web/ajax/modals/server.php');
$passes = 0;
$failures = 0;
if (!preg_match('/^.*name="newServer\[Port\]".*$/m', $src, $m)) {
  echo "not ok - Port input not found\n";
  exit(1);
}
foreach (array('min="1"', 'max="65535"') as $attr) {
  if (strpos($m[0], $attr) !== false) {
    $passes++;
    echo "ok - Port input has $attr\n";
  } else {
    $failures++;
    echo "not ok - Port input lacks $attr: ".$m[0]."\n";
  }
}
$monitor = file_get_contents(__DIR__.'/../../web/skins/classic/views/monitor.php');
preg_match_all('/^.*name="newMonitor\[Port\]".*$/m', $monitor, $all);
foreach ($all[0] as $line) {
  if (strpos($line, 'max="65536"') === false) {
    $passes++;
    echo "ok - monitor Port input stays within 65535\n";
  } else {
    $failures++;
    echo "not ok - monitor Port input allows 65536: ".trim($line)."\n";
  }
}
echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
