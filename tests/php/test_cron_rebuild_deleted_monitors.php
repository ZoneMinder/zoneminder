<?php
// Run: php tests/php/test_cron_rebuild_deleted_monitors.php   (from the repo root)

$root = __DIR__.'/../..';
$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

$dir = sys_get_temp_dir().'/zm_cron_rebuild_'.getmypid();
@mkdir($dir.'/bin', 0777, true);
file_put_contents($dir.'/bin/crontab', "#!/bin/sh\nif [ \"\$1\" = -l ]; then exit 1; fi\ncat > \"$dir/installed\"\n");
chmod($dir.'/bin/crontab', 0755);
putenv('PATH='.$dir.'/bin:'.getenv('PATH'));

file_put_contents($dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbFetchAll($sql, $col = null, $params = null) {
  if (strpos($sql, 'FROM `CronJobs`') !== false) return array();
  if (strpos($sql, 'FROM `MonitorActions`') !== false) {
    $rows = array();
    foreach (array(1 => array(10, 20), 2 => array(10, 21), 3 => array(10, 22), 4 => array(23, 10)) as $id => $ends) {
      $rows[] = array('Id' => $id, 'MonitorId' => $ends[0], 'TriggerOn' => 'Schedule',
        'Schedule' => '0 8 * * *', 'ActionType' => 'LightOn', 'TargetMonitorId' => $ends[1], 'Enabled' => 1);
    }
    return $rows;
  }
  if (strpos($sql, 'FROM `Monitors`') !== false) {
    $id = $params[0];
    if ($id == 22) return array();
    return array(array('Id' => $id, 'Deleted' => ($id == 21 or $id == 23) ? 1 : 0, 'ServerId' => null));
  }
  return array();
}
function dbFetchOne($sql, $col = null, $params = null) { return null; }
function dbQuery($sql, $params = null) { return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
set_include_path($dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
define('ZM_PATH_BIN', '/usr/bin');
require_once($root.'/web/includes/CronTab.php');

check('rebuild succeeds', cronRebuild() !== false);
$installed = @file_get_contents($dir.'/installed');
check('action on live monitors is written', strpos($installed, 'ZoneMinder action 1') !== false, $installed);
check('action targeting a deleted monitor is left out', strpos($installed, 'ZoneMinder action 2') === false, $installed);
check('action targeting a missing monitor is left out', strpos($installed, 'ZoneMinder action 3') === false, $installed);
check('action owned by a deleted monitor is left out', strpos($installed, 'ZoneMinder action 4') === false, $installed);

exec('rm -rf '.escapeshellarg($dir));
exit($failures ? 1 : 0);
