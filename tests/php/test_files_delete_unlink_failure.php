<?php
/*
 * The files delete action ignored the result of unlink(), so a file that could
 * not be removed (no write permission on its folder, read-only mount) was left
 * in place with no error shown. Run it as a non-root user: root ignores the
 * folder permission.
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
  require_once($root.'/web/includes/log_redact.php');

  function canView($r) { return true; }
  function canEdit($r) { return true; }
  function detaintPath($p) { return $p; }
  function detaintPathAllowAbsolute($p) { return $p; }
  function validHtmlStr($s) { return htmlspecialchars($s); }

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

  if (function_exists('posix_geteuid') and posix_geteuid() == 0) {
    echo "skipped: run as a non-root user\n";
    exit(0);
  }

  $tmp = sys_get_temp_dir().'/zm_files_unlink_'.getmypid();
  $store = $tmp.'/events';
  mkdir($store.'/1', 0777, true);
  file_put_contents($store.'/1/a.jpg', 'a');
  chmod($store.'/1', 0555);

  $action = 'delete';
  $error_message = '';
  $_REQUEST = array('path' => $store.'/1', 'files' => array('a.jpg'));
  include $root.'/web/includes/actions/files.php';
  check('the file is still there', file_exists($store.'/1/a.jpg'));
  check('the failure is reported', strpos((string)$error_message, 'Unable to delete a.jpg') !== false);

  chmod($store.'/1', 0777);
  $error_message = '';
  include $root.'/web/includes/actions/files.php';
  check('a removable file is deleted', !file_exists($store.'/1/a.jpg'));
  check('no error for a removed file', $error_message === '');

  shell_exec('rm -rf '.escapeshellarg($tmp));
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
