<?php
// Logs.TimeKey is decimal(16,6) but the Log view shows it truncated to the
// second. An End Date/Time of 12:00:00 has to include a row logged at
// 12:00:00.4, which the view displays as 12:00:00.
// Usage: php test_log_end_datetime.php <root containing web/>
$root = $argv[1] ?? dirname(__DIR__, 2);
$GLOBALS['queries'] = array();

function dbQuery($sql, $params = null) {
  $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
  return true;
}
function canEdit($area) {
  return true;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function ajaxError($message) {
}
function ajaxResponse($data = array()) {
}
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

date_default_timezone_set('UTC');
$_REQUEST = array('task' => 'delete', 'all' => 1, 'EndDateTime' => '2026-01-02 12:00:00');
require($root.'/web/ajax/log.php');

// Evaluate the generated clause against a row logged at 12:00:00.4.
$q = $GLOBALS['queries'][0];
$timeKey = strtotime('2026-01-02 12:00:00') + 0.4;
$bound = $q['params'][0];
if (strpos($q['sql'], 'TimeKey <= ?') !== false) $match = $timeKey <= $bound;
else if (strpos($q['sql'], 'TimeKey < ?') !== false) $match = $timeKey < $bound;
else $match = null;
$ok = ($match === true);
printf("[%s] a row at 12:00:00.4 is inside End Date/Time 12:00:00 (sql: %s, bound: %s)\n",
  $ok ? 'PASS' : 'FAIL', $q['sql'], var_export($bound, true));
exit($ok ? 0 : 1);
