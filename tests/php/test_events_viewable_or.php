<?php
// ajax/events.php limits a user who cannot view every monitor to the monitors
// they can view by adding a MonitorId term to the request's filter. The filter
// comes from the request and may use OR, so "Name = a OR Name = b" with the
// limit added as "AND MonitorId IN (...)" read as "a OR (b AND limit)" and
// matched every event named a, on any monitor.
//
// The test builds the real filter from the request and checks that no branch
// of a top-level OR in the SQL lacks the monitor limit.
//
// Run as: php tests/php/test_events_viewable_or.php   (from the repo root)

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function Info($m) {}
  class Event {
    public function __construct($r = null) {}
  }
  class Storage {
    public static function find() { return array(); }
  }
}

namespace {
  class Done extends \Exception {}

  $webroot = (isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..').'/web';
  $stubdir = sys_get_temp_dir().'/zm_events_or_stubs_'.getmypid();
  @mkdir($stubdir, 0700, true);
  foreach (array('database.php', 'Monitor.php', 'mf.php') as $stub) file_put_contents($stubdir.'/'.$stub, "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.$webroot.'/includes'.PATH_SEPARATOR.$webroot.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubdir) {
    foreach (array('database.php', 'Monitor.php', 'mf.php') as $f) @unlink($stubdir.'/'.$f);
    @rmdir($stubdir);
  });

  define('ZM_WEB_LIST_THUMB_WIDTH', 100);
  define('ZM_WEB_VIDEO_MAXFPS', 30);
  define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
  define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
  class TestUser {
    public function Username() { return 'tester'; }
    public function unviewableMonitorIds() { return array(3); }
    public function viewableMonitorIds() { return array(1, 2); }
  }
  class Fmt { public function format($t) { return ''; } }
  $user = new TestUser();
  $dateTimeFormatter = new Fmt();

  function canView($a, $m = false) { return true; }
  function canEdit($a, $m = false) { return true; }
  function getSkinFile($f) { return $GLOBALS['stubdir'].'/mf.php'; }
  function getFilteredMonitorIds() { return null; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function translate($s) { return $s; }
  function jsonEncode($v) { return json_encode($v); }
  function jsonDecode($s) { return json_decode($s, true); }
  function human_filesize($n) { return $n.'B'; }
  function ajaxError($m) { throw new Done(); }
  function ajaxResponse($d) { throw new Done(); }
  $queries = array();
  function dbFetchOne($sql, $col = false, $params = null) { $GLOBALS['queries'][] = $sql; return 0; }
  function dbFetchAll($sql, $col = false, $params = null) { $GLOBALS['queries'][] = $sql; return array(); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = $sql; return true; }
  function dbEscape($s) { return addslashes($s); }
  function dbFetchNext($q) { return false; }
  function dbError($sql) { return ''; }

  $_REQUEST = array('task' => 'query', 'sort' => 'StartDateTime', 'order' => 'asc',
    'filter' => array('Query' => array('terms' => array(
      array('attr' => 'Name', 'op' => '=', 'val' => 'a'),
      array('cnj' => 'or', 'attr' => 'Name', 'op' => '=', 'val' => 'b'),
    ))));
  try {
    include $webroot.'/ajax/events.php';
  } catch (Done $e) {
  }

  // Split the WHERE clause at top-level " or ", and report each branch that
  // does not hold the monitor limit.
  function unlimited_branches($sql) {
    if (!preg_match('/WHERE (.*?)(?: GROUP BY| ORDER BY|$)/s', $sql, $m)) return array('no WHERE clause');
    // The filter's SQL sits in the brackets after "M.Deleted = false AND".
    if (!preg_match('/^M\.Deleted = false AND \((.*)\)\s*$/s', trim($m[1]), $f)) return array('no filter clause');
    $where = preg_replace('/\s+/', ' ', $f[1]);
    $branches = array(''); $depth = 0; $i = 0; $n = strlen($where);
    while ($i < $n) {
      $c = $where[$i];
      if ($c == '(') $depth++;
      if ($c == ')') $depth--;
      if ($depth == 0 and strncasecmp(substr($where, $i), ' or ', 4) == 0) {
        $branches[] = ''; $i += 4; continue;
      }
      $branches[count($branches)-1] .= $c;
      $i++;
    }
    return array_values(array_filter($branches, function($b) { return stripos($b, 'MonitorId IN') === false; }));
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $name\n"; return; }
    $failures++;
    echo "FAIL - $name (got ".json_encode($got).", want ".json_encode($want).")\n";
  }

  $count_sql = '';
  foreach ($queries as $q) if (stripos($q, 'COUNT(') !== false) { $count_sql = $q; break; }
  check('a count query was run', $count_sql !== '', true);
  check('every OR branch of the filter holds the monitor limit', unlimited_branches($count_sql), array());
  exit($failures ? 1 : 0);
}
