<?php
// ajax/events.php task=archive / task=unarchive used to answer a plain {result:'Ok'}
// even when archiveRequest() refused (event missing, or no permission on the event)
// or the UPDATE failed. archiveRequest() now returns a message ('' on success) and
// the switch collects $data['message'][] / $data['notChanged'][] like task=delete.
//
// events.php declares functions, so each scenario runs in a child php process.
// The repo root can be overridden with argv[1] (default __DIR__.'/../..').
//
// Run as: php tests/php/test_ajax_events_archive_refused.php [repo-root]

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  class Filter {
    public $terms = array();
    public static function parse($f) { return new Filter(); }
    public static function buildSortSql($sort, $order, $resolve) { return $resolve($sort).' '.$order; }
    public function addTerm($t) { return $this; }
    public function addTerms($t, $o = null) { return $this; }
    public function sql() { return 'E.Id > 0'; }
    public function post_sql_conditions() { return array('x'); }
    public function test_pre_sql_conditions() { return true; }
    public function test_post_sql_conditions($e) { return true; }
    public function limit() { return 0; }
    public function sort_asc() { return true; }
    public function sort_field() { return 'StartDateTime'; }
  }
  class Event {
    private $id;
    public function __construct($row) {
      $this->id = is_array($row) ? (isset($row['Id']) ? $row['Id'] : 0) : $row;
    }
    public function Id() {
      return isset($GLOBALS['event_found'][$this->id]) ? $GLOBALS['event_found'][$this->id] : 0;
    }
    public function canView() {
      return !empty($GLOBALS['event_canview'][$this->id]);
    }
    public function canEdit() {
      return !empty($GLOBALS['event_canedit'][$this->id]);
    }
    public function Archived() { return false; }
    public function Width() { return 0; }
    public function Height() { return 0; }
    public function ThumbnailWidth() { return 0; }
    public function ThumbnailHeight() { return 0; }
    public function DefaultVideo() { return ''; }
    public function DiskSpace() { return 0; }
    public function getThumbnailSrc($a, $s) { return ''; }
    public function getStreamSrc($a, $s) { return ''; }
  }
  class Storage {
    public static function find() { return array(); }
  }
}

namespace {
  class Done extends \Exception {}

  $scenarios = array('a', 'b', 'c');

  if (!isset($argv[1]) || !in_array($argv[1], $scenarios, true)) {
    $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
    $failures = 0;
    foreach ($scenarios as $scenario) {
      passthru(
        escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.
        escapeshellarg($scenario).' '.escapeshellarg($root),
        $rc
      );
      if ($rc) $failures++;
    }
    echo ($failures ? 'FAIL' : 'ok').' - '.count($scenarios).' scenarios, '.$failures." failed\n";
    exit($failures ? 1 : 0);
  }

  $scenario = $argv[1];
  $root = rtrim(isset($argv[2]) ? $argv[2] : __DIR__.'/../..', '/');

  $stubdir = sys_get_temp_dir().'/zm_events_arch_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  file_put_contents($stubdir.'/mf.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubdir) {
    @unlink($stubdir.'/includes/Filter.php'); @unlink($stubdir.'/mf.php');
    @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  });

  define('ZM_WEB_LIST_THUMB_WIDTH', 100);
  define('ZM_WEB_VIDEO_MAXFPS', 30);
  class TestUser {
    public function Username() { return 'tester'; }
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(); }
  }
  class Fmt { public function format($t) { return ''; } }
  $user = new TestUser();
  $dateTimeFormatter = new Fmt();

  function canView($a, $m = false) { return true; }
  function canEdit($a, $m = false) { return !empty($GLOBALS['global_can_edit']); }
  function getSkinFile($f) { return $GLOBALS['stubdir'].'/mf.php'; }
  function getFilteredMonitorIds() { return null; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function translate($s) { return $s; }
  function human_filesize($n) { return $n.'B'; }
  function ajaxError($m) { $GLOBALS['err'] = $m; throw new Done(); }
  function ajaxResponse($result = false) {
    $response = array('result' => 'Ok');
    if (is_array($result)) {
      $response = array_merge($response, $result);
    } else if (!empty($result)) {
      $response['message'] = $result;
    }
    $GLOBALS['out'] = $response;
    throw new Done();
  }
  $updates = array();
  function dbQuery($sql, $params = array()) {
    if (stripos($sql, 'UPDATE') === 0) { $GLOBALS['updates'][] = $params; return $GLOBALS['db_ok']; }
    $GLOBALS['pending'] = array();
    return true;
  }
  function dbFetchNext($q) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql, $col = false, $params = null) { return array(); }
  function dbFetchOne($sql, $col = false, $params = null) { return 0; }
  function dbError($sql) { return ''; }

  set_error_handler(function($no, $str) { return true; });

  $GLOBALS['global_can_edit'] = true;
  $GLOBALS['db_ok'] = true;
  $GLOBALS['event_found'] = array();
  $GLOBALS['event_canview'] = array();
  $GLOBALS['event_canedit'] = array();
  $GLOBALS['err'] = null;
  $GLOBALS['updates'] = array();

  switch ($scenario) {
  case 'a':
    // Global Events edit is allowed, but the event itself denies unarchive.
    $_REQUEST = array('task' => 'unarchive', 'eids' => array(5));
    $GLOBALS['event_found'][5] = 5;
    $GLOBALS['event_canedit'][5] = false;
    break;
  case 'b':
    // Archiving an event that does not exist.
    $_REQUEST = array('task' => 'archive', 'eids' => array(7));
    $GLOBALS['event_found'][7] = 0;
    break;
  case 'c':
    // Archiving an event the user can view.
    $_REQUEST = array('task' => 'archive', 'eids' => array(9));
    $GLOBALS['event_found'][9] = 9;
    $GLOBALS['event_canview'][9] = true;
    break;
  }

  $out = null;
  try {
    include $root.'/web/ajax/events.php';
  } catch (Done $e) {
  } catch (\Throwable $e) {
    $out = 'threw: '.$e->getMessage();
  }

  $out = $GLOBALS['out'];
  $err = $GLOBALS['err'];
  $updates = $GLOBALS['updates'];
  $problems = array();

  switch ($scenario) {
  case 'a':
    $name = 'unarchive refusal reports the event and does not update';
    if ($err !== null) $problems[] = 'ajaxError: '.$err;
    if (!is_array($out) || !isset($out['result']) || $out['result'] !== 'Ok') {
      $problems[] = 'no Ok response: '.var_export($out, true);
    } else {
      if (empty($out['message']) || !is_array($out['message']) ||
          strpos(implode(' ', $out['message']), '5') === false) {
        $problems[] = 'message does not mention 5: '.var_export(isset($out['message']) ? $out['message'] : null, true);
      }
      if (!isset($out['notChanged']) || $out['notChanged'] != array(5)) {
        $problems[] = 'notChanged is not [5]: '.var_export(isset($out['notChanged']) ? $out['notChanged'] : null, true);
      }
    }
    if ($updates) $problems[] = 'dbQuery was called: '.var_export($updates, true);
    break;
  case 'b':
    $name = 'archive of a missing event reports it';
    if ($err !== null) $problems[] = 'ajaxError: '.$err;
    if (!is_array($out) || !isset($out['result']) || $out['result'] !== 'Ok') {
      $problems[] = 'no Ok response: '.var_export($out, true);
    } else {
      if (empty($out['message']) || !is_array($out['message']) ||
          strpos(implode(' ', $out['message']), '7') === false) {
        $problems[] = 'message does not mention 7: '.var_export(isset($out['message']) ? $out['message'] : null, true);
      }
      if (!isset($out['notChanged']) || $out['notChanged'] != array(7)) {
        $problems[] = 'notChanged is not [7]: '.var_export(isset($out['notChanged']) ? $out['notChanged'] : null, true);
      }
    }
    break;
  case 'c':
    $name = 'successful archive updates the event and reports nothing';
    if ($err !== null) $problems[] = 'ajaxError: '.$err;
    if (!is_array($out) || !isset($out['result']) || $out['result'] !== 'Ok') {
      $problems[] = 'no Ok response: '.var_export($out, true);
    } else {
      if (array_key_exists('message', $out)) $problems[] = 'unexpected message: '.var_export($out['message'], true);
      if (array_key_exists('notChanged', $out)) $problems[] = 'unexpected notChanged: '.var_export($out['notChanged'], true);
    }
    if (count($updates) !== 1 || $updates[0] !== array(1, 9)) {
      $problems[] = 'expected UPDATE [1,9], got '.var_export($updates, true);
    }
    break;
  }

  if ($problems) {
    echo "FAIL - $scenario: $name (".implode('; ', $problems).")\n";
    exit(1);
  }
  echo "ok - $scenario: $name\n";
  exit(0);
}
