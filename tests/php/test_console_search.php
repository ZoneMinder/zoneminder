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

eval('namespace ZM { class Monitor { private $row; public function __construct($row) { $this->row = $row; } public function Source() { return preg_replace(\'/^.*@/\', \'\', (string)$this->row[\'Host\']); } public function Path() { return preg_replace(\'#//[^/@]*@#\', \'//\', (string)$this->row[\'Path\']); } } }');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$path = $root.'/web/ajax/console.php';
$src = file_get_contents($path);
if (!preg_match('/^function monitorMatchesSearch\(.*?\n\}/ms', $src, $m)) {
  echo "Could not find monitorMatchesSearch() in $path\n";
  exit(1);
}
eval($m[0]);
// The helper it calls, when this version has one.
if (preg_match('/^function pathWithoutQuery\(.*?\n\}/ms', $src, $h)) eval($h[0]);

$front = array('Id'=>3, 'Name'=>'Front Door', 'Function'=>'Monitor', 'Path'=>'rtsp://10.0.0.5/live',
  'Device'=>null, 'Host'=>null, 'Status'=>'Running');
$local = array('Id'=>12, 'Name'=>'Garage', 'Function'=>'Monitor', 'Path'=>'', 'Device'=>'/dev/video0',
  'Host'=>'cam.example', 'Status'=>null);
$cred = array('Id'=>20, 'Name'=>'Porch', 'Path'=>'rtsp://admin:hunter2@10.0.0.9/live', 'Device'=>null, 'Host'=>'root:s3cret@cam2.example', 'Status'=>null);

check('name, any case', monitorMatchesSearch($front, 'door'), true);
check('path', monitorMatchesSearch($front, '10.0.0.5'), true);
check('device', monitorMatchesSearch($local, 'video0'), true);
check('host', monitorMatchesSearch($local, 'EXAMPLE'), true);
check('id', monitorMatchesSearch($local, '12'), true);
check('status', monitorMatchesSearch($front, 'running'), true);
check('legacy Function column is not searched', monitorMatchesSearch($front, 'monitor'), false);
check('no match', monitorMatchesSearch($local, 'driveway'), false);
check('missing status row', monitorMatchesSearch($local, 'running'), false);

$qcred = array('Id'=>21, 'Name'=>'Gate', 'Path'=>'http://10.0.0.10/snap.cgi?user=admin&pwd=hunter3', 'Device'=>null, 'Host'=>'10.0.0.10', 'Status'=>null);
check('credentials in the path query are not searched', monitorMatchesSearch($qcred, 'hunter3'), false);
check('the path before the query is still searched', monitorMatchesSearch($qcred, 'snap.cgi'), true);
check('credentials in the path are not searched', monitorMatchesSearch($cred, 'hunter2'), false);
check('credentials in the host are not searched', monitorMatchesSearch($cred, 's3cret'), false);
check('path host is still searched', monitorMatchesSearch($cred, '10.0.0.9'), true);
check('host without credentials is still searched', monitorMatchesSearch($cred, 'cam2.example'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
