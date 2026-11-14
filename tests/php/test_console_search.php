<?php

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

$path = __DIR__.'/../../web/ajax/console.php';
$src = file_get_contents($path);
if (!preg_match('/^function monitorMatchesSearch\(.*?\n\}/ms', $src, $m)) {
  echo "Could not find monitorMatchesSearch() in $path\n";
  exit(1);
}
eval($m[0]);

$front = array('Id'=>3, 'Name'=>'Front Door', 'Function'=>'Monitor', 'Path'=>'rtsp://10.0.0.5/live',
  'Device'=>null, 'Host'=>null, 'Status'=>'Running');
$local = array('Id'=>12, 'Name'=>'Garage', 'Function'=>'Monitor', 'Path'=>'', 'Device'=>'/dev/video0',
  'Host'=>'cam.example', 'Status'=>null);

check('name, any case', monitorMatchesSearch($front, 'door'), true);
check('path', monitorMatchesSearch($front, '10.0.0.5'), true);
check('device', monitorMatchesSearch($local, 'video0'), true);
check('host', monitorMatchesSearch($local, 'EXAMPLE'), true);
check('id', monitorMatchesSearch($local, '12'), true);
check('status', monitorMatchesSearch($front, 'running'), true);
check('legacy Function column is not searched', monitorMatchesSearch($front, 'monitor'), false);
check('no match', monitorMatchesSearch($local, 'driveway'), false);
check('missing status row', monitorMatchesSearch($local, 'running'), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
