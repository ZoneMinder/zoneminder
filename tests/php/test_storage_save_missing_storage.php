<?php
// Options > Storage save built the area from the posted id and saved it whether or not the
// row still existed. Editing an area that was deleted from another tab inserted a new area
// with the posted Name and Path instead of saying it is gone. Runs the real
// includes/actions/storage.php against the real Storage class.
//
// Run: php tests/php/test_storage_save_missing_storage.php [web-root]
// web-root defaults to this repository's web/ directory.

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $o, $id, $m) {}
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';
  $stubdir = sys_get_temp_dir().'/zm_storage_missing_stubs_'.getmypid();
  @mkdir($stubdir, 0700, true);
  file_put_contents($stubdir.'/database.php', "<?php\n");
  file_put_contents($stubdir.'/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.$root.'/includes'.PATH_SEPARATOR.get_include_path());
  define('ZM_DIR_EVENTS', '/var/cache/zoneminder/events');

  function canEdit($a) { return true; }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    if ($params[0] != 5) return false;
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
    $writes = array();
    foreach ($queries as $q) {
      if (preg_match('/^\s*(UPDATE|INSERT)/i', $q[0], $m)) $writes[] = strtoupper($m[1]);
    }
    return array($error_message, $writes);
  }

  list($err, $writes) = run_save(5, array('Name'=>'Main2', 'Path'=>'/mnt/a'));
  check('editing an existing area updates it', $writes, array('UPDATE'));
  check('editing an existing area has no error', $err, '');

  list($err, $writes) = run_save(0, array('Name'=>'New', 'Path'=>'/mnt/b'));
  check('a new area is inserted', $writes, array('INSERT'));

  list($err, $writes) = run_save(7, array('Name'=>'Gone', 'Path'=>'/mnt/c'));
  check('a deleted area is not inserted again', $writes, array());
  check('a deleted area reports that it is gone', $err, 'Storage area not found<br/>');

  @unlink($stubdir.'/database.php');
  @unlink($stubdir.'/Event.php');
  @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
