<?php

namespace ZM {
  class Zone {
    private $id;
    function __construct($id) {
      $this->id = $id;
    }
    function Id() {
      return $this->id == 5 ? 5 : 0;
    }
    function Monitor() {
      return new Monitor();
    }
    function delete() {
      return true;
    }
  }

  class Monitor {
    function canEdit() {
      return false;
    }
    function Name() {
      return '<b>cam</b>';
    }
    function Id() {
      return 1;
    }
  }

  function AuditAction(...$a) {
  }
}

namespace {
  function validCardinal($v) {
    return (int)$v;
  }
  function validHtmlStr($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES);
  }
  function daemonCheck() {
    return false;
  }
  function dbLastError() {
    return '';
  }

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $failed = 0;

  function check($name, $condition) {
    global $failed;
    if ($condition) {
      echo "ok - $name\n";
    } else {
      echo "FAIL - $name\n";
      $failed++;
    }
  }

  $tmpdir = sys_get_temp_dir().'/zm_zones_delete_test_'.uniqid();
  mkdir($tmpdir.'/includes', 0777, true);
  file_put_contents($tmpdir.'/includes/Zone.php', "<?php\n");

  $cwd = getcwd();
  chdir($tmpdir);

  $action = 'delete';
  $error_message = '';
  $redirect = null;
  $_REQUEST = array('markZids' => array('<img src=x onerror=alert(1)>', '5'));

  require_once($root.'/web/includes/actions/zones.php');

  chdir($cwd);
  unlink($tmpdir.'/includes/Zone.php');
  rmdir($tmpdir.'/includes');
  rmdir($tmpdir);

  check('raw img tag is escaped', strpos($error_message, '<img src=x') === false);
  check('raw monitor name is escaped', strpos($error_message, '<b>cam</b>') === false);
  check('escaped img tag present', strpos($error_message, '&lt;img src=x') !== false);
  check('escaped monitor name present', strpos($error_message, '&lt;b&gt;cam&lt;/b&gt;') !== false);

  exit($failed ? 1 : 0);
}
