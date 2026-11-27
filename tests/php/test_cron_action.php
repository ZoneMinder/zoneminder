<?php
// The scheduled task action must not fatal or warn when a field arrives as
// an array or the id list is not one.
namespace ZM {
  function Warning($m) {}
  function Info($m) {}
  function Error($m) {}
  class CronJob {
    public function Id() { return 0; }
    public function insert($v) { $GLOBALS['inserted'] = $v; return true; }
    public function get_last_error() { return ''; }
    public static function find_one() { return null; }
  }
}
namespace {
  function canEdit($x) { return true; }
  function translate($s) { return $s; }
  function validInt($v) { return (int)$v; }
  function cronScheduleIsValid($s) { return $s === '0 3 * * *'; }
  function cronCommandIsValid($s) { return $s !== ''; }
  function commandAllowedByConfig($s) { return true; }
  function commandRefusedMessage() { return 'refused'; }
  function cronRebuild() { return array(); }
  function dbQuery($sql, $p = array()) { $GLOBALS['deleted'][] = $p; return true; }

  $dir = sys_get_temp_dir().'/zm_cron_action_'.getmypid();
  mkdir($dir.'/includes', 0777, true);
  foreach (array('CronJob', 'CronTab', 'Command') as $f) file_put_contents($dir.'/includes/'.$f.'.php', "<?php\n");
  chdir($dir);

  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

  $fail = 0;
  $check = function($name, $ok) use (&$fail) {
    echo ($ok ? 'ok   ' : 'FAIL ').$name."\n";
    if (!$ok) $fail++;
  };
  $run = function($action, $request) {
    $_REQUEST = $request;
    $GLOBALS['action'] = $action;
    $action_file = __DIR__.'/../../web/includes/actions/cron.php';
    $redirect = null;
    include $action_file;
    return $cron_error;
  };

  $inserted = null;
  try {
    $run('save', array('id'=>0, 'newJob'=>array('Schedule'=>'0 3 * * *', 'Command'=>array('x'), 'Name'=>array('y'))));
    $check('array Command is refused, not a TypeError', $inserted === null);
  } catch (\Throwable $e) {
    $check('array Command is refused, not a TypeError ('.get_class($e).')', false);
  }

  $warnings = array();
  $run('delete', array('markIds'=>'7'));
  $check('non-array markIds raises no warning', count($warnings) === 0);

  foreach (array("CronJob", "CronTab", "Command") as $f) unlink($dir."/includes/".$f.".php");
  rmdir($dir."/includes");
  chdir("/");
  rmdir($dir);
  exit($fail ? 1 : 0);
}
