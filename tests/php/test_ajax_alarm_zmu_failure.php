<?php
// web/ajax/alarm.php must report an error when zmu fails (monitor not running),
// not {"result":"Ok"}. Runs alarm.php with stubbed helpers and a fake zmu.
//
// Run as: php tests/php/test_ajax_alarm_zmu_failure.php [web-root]

$root = realpath($argv[1] ?? __DIR__.'/../../web');
$tmp = sys_get_temp_dir().'/alarmtest'.getmypid();
mkdir($tmp.'/includes', 0777, true);
file_put_contents($tmp.'/includes/Monitor.php', <<<'PHP'
<?php
namespace ZM {
class Monitor { function __construct($id) {} function Id() { return 1; } function canEdit() { return true; } }
}
namespace {
function validCardinal($v) { return (int)$v; }
function validJsStr($v) { return $v; }
function getZmuCommand($a) { return 'sh '.getenv('FAKE_ZMU').$a; }
function ajaxError($m) { echo json_encode(['result'=>'Error','message'=>$m]); exit; }
function ajaxResponse($r=false) { echo json_encode(['result'=>'Ok','message'=>$r]); exit; }
}
PHP);
file_put_contents($tmp.'/ok.sh', "echo 'Alarmed event id: 5'\nexit 0\n");
file_put_contents($tmp.'/fail.sh', "exit 255\n");
file_put_contents($tmp.'/run.php', '<?php $_REQUEST=["id"=>1,"command"=>"forceAlarm"]; chdir($argv[1]); require "ajax/alarm.php";');

function run($zmu) {
  global $root, $tmp;
  $cmd = 'FAKE_ZMU='.escapeshellarg("$tmp/$zmu").' php -d include_path='.escapeshellarg("$tmp:$root")
    .' '.escapeshellarg("$tmp/run.php").' '.escapeshellarg($root).' 2>&1';
  $out = shell_exec($cmd); $j = json_decode($out, true); return $j ?? ["raw"=>$out];
}
$fail = 0;
$r = run('ok.sh');
if (($r['result'] ?? '') !== 'Ok') { echo "FAIL - successful zmu: ".json_encode($r)."\n"; $fail++; } else echo "ok - successful zmu gives Ok\n";
$r = run('fail.sh');
if (($r['result'] ?? '') !== 'Error') { echo "FAIL - failing zmu reported as ".json_encode($r)."\n"; $fail++; } else echo "ok - failing zmu gives Error\n";
shell_exec('rm -rf '.escapeshellarg($tmp));
exit($fail ? 1 : 0);
