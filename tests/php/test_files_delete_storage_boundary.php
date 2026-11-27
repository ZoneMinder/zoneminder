<?php
/*
 * The files delete action must only delete below a Storage path, not in a
 * sibling directory that merely starts with the same characters.
 *
 * Run: php tests/php/test_files_delete_storage_boundary.php [root]
 */
namespace ZM {
  class Storage {
    public $p;
    function __construct($p) { $this->p = $p; }
    function Path() { return $this->p; }
    static function find() { return array(new Storage($GLOBALS['store'])); }
  }
  function Warning($s) {}
  function Debug($s) {}
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  function canView($r) { return true; }
  function canEdit($r) { return true; }
  function detaintPath($p) { return $p; }
  function detaintPathAllowAbsolute($p) { return $p; }

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n";
    }
  }

  $tmp = sys_get_temp_dir().'/zm_files_del_'.getmypid();
  $store = $tmp.'/events';
  mkdir($store.'/1', 0777, true);
  mkdir($tmp.'/events2', 0777, true);
  file_put_contents($store.'/1/a.jpg', 'a');
  file_put_contents($tmp.'/events2/b.jpg', 'b');
  file_put_contents($tmp.'/c.jpg', 'c');

  $action = 'delete';
  $error_message = '';
  $_REQUEST = array('path' => $tmp.'/events2', 'files' => array('b.jpg'));
  include $root.'/web/includes/actions/files.php';
  check('a sibling directory with the same prefix is refused', file_exists($tmp.'/events2/b.jpg'));

  // A path that starts with the Storage path and climbs out with "..".
  $_REQUEST = array('path' => $store.'/..', 'files' => array('c.jpg'));
  include $root.'/web/includes/actions/files.php';
  check('a path that climbs out of the Storage area is refused', file_exists($tmp.'/c.jpg'));

  $_REQUEST = array('path' => $store.'/1', 'files' => array('a.jpg'));
  include $root.'/web/includes/actions/files.php';
  check('a file below the Storage path is deleted', !file_exists($store.'/1/a.jpg'));

  shell_exec('rm -rf '.escapeshellarg($tmp));
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
