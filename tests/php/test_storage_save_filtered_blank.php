<?php
// Options > Storage save: a Name or Path made only of characters the input
// filter strips (Path '+++' or '/', Name '###') passes the blank check, is
// filtered to '' and then ignored by the setters, so the save did nothing (or
// created the area on the default events path). It must be refused instead.
// Runs the real includes/actions/storage.php against the real Storage class.
//
// Run: php tests/php/test_storage_save_filtered_blank.php [web-root]
// web-root defaults to this repository's web/ directory.

namespace ZM {
  $GLOBALS['saves'] = array();
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $o, $id, $m) {}
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';
  $stubdir = sys_get_temp_dir().'/zm_storage_blank_stubs_'.getmypid();
  @mkdir($stubdir, 0700, true);
  file_put_contents($stubdir.'/database.php', "<?php\n");
  file_put_contents($stubdir.'/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.$root.'/includes'.PATH_SEPARATOR.get_include_path());
  define('ZM_DIR_EVENTS', '/var/cache/zoneminder/events');

  function canEdit($a) { return true; }
  function validCardinal($v) { return (int)$v; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return array('Id'=>5, 'Name'=>'Main', 'Path'=>'/mnt/a', 'Type'=>'local', 'Url'=>'',
      'DiskSpace'=>null, 'Scheme'=>'Medium', 'ServerId'=>0, 'DoDelete'=>1, 'Enabled'=>1);
  }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }
  function dbInsertId() { return 9; }
  function dbLastError() { return ''; }
  function dbError($sql) { return ''; }

  require_once 'Storage.php';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  function run_save($id, $new) {
    global $root, $error_message, $queries;
    $error_message = '';
    $queries = array();
    $action = 'save';
    $_REQUEST = array('id'=>$id, 'newStorage'=>$new);
    unset($redirect);
    include $root.'/includes/actions/storage.php';
    $writes = 0;
    foreach ($queries as $q) {
      if (preg_match('/^\s*(UPDATE|INSERT)/i', $q[0])) $writes++;
    }
    return array($error_message, $writes);
  }

  list($err, $writes) = run_save(5, array('Name'=>'Main', 'Path'=>'+++'));
  check('edit with Path of only stripped chars is refused', $err !== '', true);
  check('edit with Path of only stripped chars writes nothing', $writes, 0);

  list($err, $writes) = run_save(5, array('Name'=>'Main', 'Path'=>'/'));
  check('edit with Path "/" is refused', $err !== '', true);
  check('edit with Path "/" writes nothing', $writes, 0);

  list($err, $writes) = run_save(5, array('Name'=>'###', 'Path'=>'/mnt/a'));
  check('edit with Name of only stripped chars is refused', $err !== '', true);
  check('edit with Name of only stripped chars writes nothing', $writes, 0);

  list($err, $writes) = run_save(0, array('Name'=>'New', 'Path'=>'+++'));
  check('new area with Path of only stripped chars is refused', $err !== '', true);
  check('new area with Path of only stripped chars writes nothing', $writes, 0);

  list($err, $writes) = run_save(5, array('Name'=>'Main', 'Path'=>'/mnt/b/'));
  check('valid edit has no error', $err, '');
  check('valid edit is written', $writes, 1);

  array_map('unlink', glob($stubdir.'/*'));
  rmdir($stubdir);
  exit($failures ? 1 : 0);
}
