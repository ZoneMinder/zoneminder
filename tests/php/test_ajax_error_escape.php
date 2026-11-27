<?php
// console.php, training.php and reports.php used to build their final
// ajaxError message from raw request data: $_REQUEST['action'] and
// $user->Username() went into the message unescaped, and reports.php passed
// dbError($sql) straight to ajaxError, leaking the SQL and the DB error to the
// browser. The fix runs the user-controlled parts through validHtmlStr() and
// answers 'Database query failed' instead of dbError($sql).
//
// Each file declares functions, so every file is exercised in a child php
// process. The directory holding the ajax files can be overridden with argv[1]
// (default __DIR__.'/../../web/ajax'); children receive the same directory.
//
// Run as: php tests/php/test_ajax_error_escape.php [ajax-dir]

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) { $GLOBALS['zm_errors'][] = $m; }
  function Fatal($m) { $GLOBALS['zm_fatals'][] = $m; }
}

namespace {
  class AjaxErrorThrown extends Exception {}

  $modes = array('console', 'training', 'reports');

  if (!isset($argv[1]) || !in_array($argv[1], $modes, true)) {
    $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/ajax';
    $failures = 0;
    foreach ($modes as $mode) {
      passthru(
        escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.
        escapeshellarg($mode).' '.escapeshellarg($root),
        $rc
      );
      if ($rc) $failures++;
    }
    echo ($failures ? 'FAIL' : 'ok').' - '.count($modes).' ajax files, '.$failures." failed\n";
    exit($failures ? 1 : 0);
  }

  $mode = $argv[1];
  $root = rtrim(isset($argv[2]) ? $argv[2] : __DIR__.'/../../web/ajax', '/');

  // includes/* are require_once'd relative to the ajax directory via include_path
  $stubdir = sys_get_temp_dir().'/zm_ajax_escape_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('Event', 'Filter', 'Report') as $stub) {
    file_put_contents($stubdir.'/includes/'.$stub.'.php', "<?php\n");
  }
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubdir) {
    foreach (array('Event', 'Filter', 'Report') as $stub) {
      @unlink($stubdir.'/includes/'.$stub.'.php');
    }
    @rmdir($stubdir.'/includes');
    @rmdir($stubdir);
  });

  if ($mode === 'training') define('ZM_OPT_TRAINING', true);

  ini_set('display_errors', '0');
  set_error_handler(function($no, $str) { return true; });

  class TestUser {
    public function Username() { return '<b>bob</b>'; }
  }
  class Fmt { public function format($t) { return '2026-11-23 09:00:00'; } }
  $user = new TestUser();
  $dateTimeFormatter = new Fmt();

  function canEdit($a, $m = false) { return false; }
  function canView($a, $m = false) { return !empty($GLOBALS['can_view']); }
  function translate($s) { return $s; }
  function validHtmlStr($v) {
    if (is_null($v) or is_array($v)) return '';
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
  function validInt($v) { return (int)$v; }
  function ajaxError($m) { throw new AjaxErrorThrown($m); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new AjaxErrorThrown('__response__'); }

  function dbQuery($sql, $params = array()) {
    if ($GLOBALS['db_query_fails']) return false;
    $GLOBALS['pending'] = array();
    return true;
  }
  function dbFetchNext($q) { return array_shift($GLOBALS['pending']); }
  function dbFetchOne($sql, $col = false, $params = null) { return 0; }
  function dbFetchAll($sql, $col = false, $params = null) { return array(); }
  function dbError($sql) { return 'SQL secret'; }

  $GLOBALS['can_view'] = ($mode === 'reports');
  $GLOBALS['db_query_fails'] = ($mode === 'reports');
  $GLOBALS['zm_errors'] = array();
  $GLOBALS['zm_fatals'] = array();
  $GLOBALS['response'] = null;

  $attack = '<script>alert(1)</script>';
  $_REQUEST = array('action' => $attack);
  if ($mode === 'reports') {
    $_REQUEST = array('task' => 'query');
  }
  $_POST = array();

  $message = null;
  $threw = null;
  try {
    include $root.'/'.$mode.'.php';
  } catch (AjaxErrorThrown $e) {
    $message = $e->getMessage();
  } catch (\Throwable $e) {
    $threw = get_class($e).': '.$e->getMessage();
  }

  $problems = array();

  if ($mode === 'console' || $mode === 'training') {
    $name = $mode.'.php final ajaxError escapes action and username';
    if ($threw !== null) {
      $problems[] = 'threw '.$threw;
    } else if ($message === null) {
      $problems[] = 'final ajaxError was not reached';
    } else {
      if (strpos($message, '<script>') !== false) $problems[] = 'raw <script> in message: '.$message;
      if (strpos($message, '<b>') !== false) $problems[] = 'raw <b> in message: '.$message;
      if (strpos($message, '&lt;script&gt;') === false) $problems[] = 'no &lt;script&gt; in message: '.$message;
      if (strpos($message, '&lt;b&gt;bob&lt;/b&gt;') === false) $problems[] = 'username not escaped in message: '.$message;
    }
  } else {
    $name = 'reports.php queryRequest answers a generic message on db failure';
    if ($threw !== null) {
      $problems[] = 'threw '.$threw;
    } else if ($message === null) {
      $problems[] = 'ajaxError was not reached';
    } else {
      if ($message !== 'Database query failed') {
        $problems[] = 'message is '.var_export($message, true);
      }
      if (strpos($message, 'SQL secret') !== false) $problems[] = 'dbError output leaked: '.$message;
    }
    $logged = implode(' ', $GLOBALS['zm_errors']);
    if (strpos($logged, 'SQL secret') === false) {
      $problems[] = 'dbError output was not logged: '.var_export($GLOBALS['zm_errors'], true);
    }
    // The raw dbError() must never be handed to ajaxError, dynamically or not.
    $src = @file_get_contents($root.'/reports.php');
    if ($src === false) {
      $problems[] = 'cannot read '.$root.'/reports.php';
    } else if (strpos($src, 'ajaxError(dbError(') !== false) {
      $problems[] = 'reports.php still calls ajaxError(dbError(';
    }
  }

  if ($problems) {
    echo "FAIL - $name (".implode('; ', $problems).")\n";
    exit(1);
  }
  echo "ok - $name\n";
  exit(0);
}
