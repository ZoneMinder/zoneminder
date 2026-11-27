<?php
// The filterdebug modal shows a filter's SQL and its EXPLAIN. EXPLAIN's row
// count is only an estimate, so the modal also runs a real COUNT of the
// filter's own query and shows how many events match right now.
//
// The count must be of the same query the modal displays (same joins and
// WHERE), but with the filter's LIMIT removed: a Limit caps how many events a
// filter acts on, it does not change how many match. When the filter has a
// Limit the modal says so beside the count.
//
// Run: php tests/php/test_filterdebug_match_count.php [root]

namespace ZM {
  class Filter {
    public static $limit = 0;

    function __construct($id = null) {}
    function set($f) {}
    function canView() { return true; }
    function Id() { return 4; }
    function limit() { return self::$limit; }

    // Marks the filter's own SQL so the count query can be traced back to it.
    function debugSql() {
      return "SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId)\n"
        ."    WHERE ( E.Name = 'sentinel_filter_term' )\n"
        ."    ORDER BY E.StartDateTime DESC\n"
        ."    LIMIT 3";
    }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  $queries = array('all' => array(), 'one' => array());

  function canView($a) { return true; }
  function validInt($i) { return (int)$i; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
  function translate($s) { return $s; }
  function getCSRFinputHTML() { return ''; }
  function dbFetchAll($sql, $col = null, $params = null) {
    $GLOBALS['queries']['all'][] = $sql;
    return array();
  }
  function dbFetchOne($sql, $col = null, $params = null) {
    $GLOBALS['queries']['one'][] = $sql;
    return $col === 'Total' ? 42 : false;
  }

  $tests = 0;
  $failures = 0;
  function check($label, $got, $want) {
    global $tests, $failures;
    $tests++;
    if ($got === $want) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n       got:  ".var_export($got, true)."\n       want: ".var_export($want, true)."\n";
    }
  }

  $tmpDir = sys_get_temp_dir().'/zm_filterdebug_count_'.getmypid();
  $includesDir = $tmpDir.'/includes';
  if (!is_dir($includesDir)) mkdir($includesDir, 0777, true);
  file_put_contents($includesDir.'/Filter.php', "<?php\n");

  $oldIncludePath = get_include_path();
  set_include_path($tmpDir.PATH_SEPARATOR.$oldIncludePath);
  register_shutdown_function(function() use ($tmpDir, $oldIncludePath) {
    set_include_path($oldIncludePath);
    @unlink($tmpDir.'/includes/Filter.php');
    @rmdir($tmpDir.'/includes');
    @rmdir($tmpDir);
  });

  function render_modal() {
    global $root, $queries;
    $queries = array('all' => array(), 'one' => array());
    $_REQUEST = array('fid' => '4');
    ob_start();
    include $root.'/web/ajax/modals/filterdebug.php';
    return ob_get_clean();
  }

  // No Limit: the count is shown, built from the filter's SQL.
  \ZM\Filter::$limit = 0;
  $html = render_modal();

  check('the modal runs exactly one count query', count($queries['one']), 1);
  $count_sql = $queries['one'] ? $queries['one'][0] : '';

  check('the count query is a COUNT', (bool)preg_match('/^\s*SELECT\s+COUNT\(\*\)/i', $count_sql), true);
  check('the count query uses the filter SQL\'s WHERE',
        strpos($count_sql, "E.Name = 'sentinel_filter_term'") !== false, true);
  check('the count query is the displayed SQL wrapped in a derived table',
        (bool)preg_match('/FROM\s*\(\s*SELECT E\.\*.*\)\s*AS FilterMatches/is', $count_sql), true);
  check('the count query drops the LIMIT', (bool)preg_match('/\bLIMIT\b/i', $count_sql), false);
  check('the count query keeps the rest of the filter SQL',
        strpos($count_sql, 'ORDER BY E.StartDateTime DESC') !== false, true);
  check('the EXPLAIN still uses the filter\'s full SQL',
        isset($queries['all'][0]) && strpos($queries['all'][0], 'LIMIT 3') !== false, true);
  check('the count is shown in the modal', strpos($html, 'Matching events: 42') !== false, true);
  check('no limit note without a limit', strpos($html, 'filter Limit') === false, true);

  // A filter Limit is reported beside the count, but does not cap the count.
  \ZM\Filter::$limit = 3;
  $html = render_modal();
  $count_sql = $queries['one'] ? $queries['one'][0] : '';

  check('a limited filter still counts all matches', (bool)preg_match('/\bLIMIT\b/i', $count_sql), false);
  check('the count is still shown', strpos($html, 'Matching events: 42') !== false, true);
  check('the modal says the filter Limit applies',
        strpos($html, 'filter Limit 3 applies') !== false, true);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
