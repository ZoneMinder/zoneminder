<?php
/*
 * The files delete action must report a failure when unlink() cannot remove a
 * file, instead of showing the normal page and letting PHP emit a warning.
 *
 * Run: php tests/php/test_files_delete_unlink_failure.php [root]
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

  if (function_exists('posix_geteuid') and posix_geteuid() === 0) {
    echo "skip\n";
    exit(0);
  }

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

  $tmp = sys_get_temp_dir().'/zm_files_del_unlink_'.getmypid();
  $store = $tmp.'/events';
  mkdir($store.'/ro', 0777, true);
  mkdir($store.'/ok', 0777, true);
  file_put_contents($store.'/ro/locked.jpg', 'locked');
  file_put_contents($store.'/ok/good.jpg', 'good');
  chmod($store.'/ro', 0555);

  // A file in a read-only directory cannot be unlinked by a normal user.
  $warnings = array();
  set_error_handler(function($errno, $errstr) use (&$warnings) {
    if (!(error_reporting() & $errno)) return false;
    $warnings[] = $errstr;
    return true;
  });

  $action = 'delete';
  $error_message = '';
  $_REQUEST = array('path' => $store.'/ro', 'files' => array('locked.jpg'));
  include $root.'/web/includes/actions/files.php';

  restore_error_handler();

  check('an undeletable file is not removed', file_exists($store.'/ro/locked.jpg'));
  check('error_message reports the failure', strpos($error_message, 'Unable to delete') !== false);
  check('no PHP warning was raised', count($warnings) === 0);

  // A deletable file is removed without reporting an error.
  $error_message = '';
  $_REQUEST = array('path' => $store.'/ok', 'files' => array('good.jpg'));
  include $root.'/web/includes/actions/files.php';
  check('a deletable file is removed', !file_exists($store.'/ok/good.jpg'));
  check('error_message is empty on success', $error_message === '');

  chmod($store.'/ro', 0777);
  shell_exec('rm -rf '.escapeshellarg($tmp));
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
