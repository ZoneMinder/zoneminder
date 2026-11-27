<?php
// Deleting scheduled tasks ignored the DELETE's result: on a database error the
// row stayed, the crontab was rebuilt and the page came back without an error.
//
// Run as: php tests/php/test_cron_delete_result.php [repo root]
namespace ZM {
  function Warning($m) {}
  function Info($m) {}
  function Error($m) {}
  class CronJob {
    public static function find_one() { return null; }
  }
}
namespace {
  $root = $argv[1] ?? __DIR__.'/../..';
  function canEdit($x) { return true; }
  function translate($s) { return $s; }
  function validInt($v) { return (int)$v; }
  function cronRebuild() { $GLOBALS['rebuilt'] = true; return array(); }
  function dbLastError() { return 'db down'; }
  function dbQuery($sql, $p = array()) {
    $GLOBALS['deletes'][] = $p[0];
    return in_array($p[0], $GLOBALS['failing']) ? null : true;
  }

  $dir = sys_get_temp_dir().'/zm_cron_delete_'.getmypid();
  mkdir($dir.'/includes', 0777, true);
  foreach (array('CronJob', 'CronTab', 'Command') as $f) file_put_contents($dir.'/includes/'.$f.'.php', "<?php\n");
  chdir($dir);

  $fail = 0;
  $check = function($name, $ok) use (&$fail) {
    echo ($ok ? 'ok   ' : 'FAIL ').$name."\n";
    if (!$ok) $fail++;
  };
  $run = function($ids, $failing) use ($root) {
    $_REQUEST = array('markIds'=>$ids);
    $GLOBALS['action'] = 'delete';
    $GLOBALS['failing'] = $failing;
    $GLOBALS['deletes'] = array();
    $GLOBALS['rebuilt'] = false;
    $action = 'delete';
    $redirect = null;
    include $root.'/web/includes/actions/cron.php';
    return array($cron_error, $redirect);
  };

  list($err, $redirect) = $run(array('3', '4'), array());
  $check('a good delete reports no error', $err === '');
  $check('a good delete rebuilds the crontab', $GLOBALS['rebuilt'] === true);
  $check('a good delete returns to the list', $redirect === '?view=cron');

  list($err, $redirect) = $run(array('3', '4'), array(4));
  $check('a failed delete is reported', strpos($err, 'Unable to delete scheduled task 4') !== false);
  $check('the database error is included', strpos($err, 'db down') !== false);
  $check('a failed delete shows the error on the page', strpos((string)$redirect, 'error=') !== false);
  $check('the other task is still deleted', $GLOBALS['deletes'] === array(3, 4));
  $check('the crontab is still rebuilt for the task that was deleted', $GLOBALS['rebuilt'] === true);

  foreach (array('CronJob', 'CronTab', 'Command') as $f) unlink($dir.'/includes/'.$f.'.php');
  rmdir($dir.'/includes');
  chdir('/');
  rmdir($dir);
  echo $fail ? "$fail failed\n" : "all passed\n";
  exit($fail ? 1 : 0);
}
