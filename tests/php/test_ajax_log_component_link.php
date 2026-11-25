<?php
$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
$GLOBALS['sql'] = array();
$GLOBALS['response'] = array();
$GLOBALS['viewable_monitors'] = array(37 => true, 5 => false);
$GLOBALS['editable_monitors'] = array(37 => false, 5 => false);
$GLOBALS['can_stream'] = true;

function dbFetchOne($sql, $col = null, $params = null) {
  return 0;
}
function dbFetchAll($sql, $col = null, $params = null) {
  $GLOBALS['sql'][] = $sql;
  if (strpos($sql, 'Logs') !== false) {
    return array(
      array(
        'Id' => 1,
        'TimeKey' => 1700000000,
        'Component' => 'zmc_m37',
        'ServerId' => 0,
        'Pid' => 1,
        'Code' => 0,
        'Message' => 'viewable',
        'File' => 'f.cpp',
        'Line' => 3,
      ),
      array(
        'Id' => 2,
        'TimeKey' => 1700000001,
        'Component' => 'zma_m5',
        'ServerId' => 0,
        'Pid' => 1,
        'Code' => 0,
        'Message' => 'not viewable',
        'File' => 'f.cpp',
        'Line' => 3,
      ),
      array(
        'Id' => 3,
        'TimeKey' => 1700000002,
        'Component' => 'zmc_m37<script>',
        'ServerId' => 0,
        'Pid' => 1,
        'Code' => 0,
        'Message' => 'looks like a monitor but is not',
        'File' => 'f.cpp',
        'Line' => 3,
      ),
      array(
        'Id' => 4,
        'TimeKey' => 1700000003,
        'Component' => 'web_js',
        'ServerId' => 0,
        'Pid' => 1,
        'Code' => 0,
        'Message' => 'other component',
        'File' => 'f.cpp',
        'Line' => 3,
      ),
    );
  }
  return array();
}
function zm_session_start() {
}
function canView($area, $mid = false) {
  if ($area === 'System') return true;
  if ($area === 'Stream') return $GLOBALS['can_stream'];
  if ($area === 'Monitors') {
    if ($mid) return !empty($GLOBALS['viewable_monitors'][$mid]);
    return true;
  }
  return true;
}
function canEdit($area, $mid = false) {
  if ($area === 'Monitors' and $mid) return !empty($GLOBALS['editable_monitors'][$mid]);
  return false;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function logState() {
  return 'ok';
}
function ajaxError($message) {
}
function ajaxResponse($data = array()) {
  $GLOBALS['response'] = $data;
}
class FakeFormatter {
  public function format($t) {
    return (string)$t;
  }
}
$dateTimeFormatter = new FakeFormatter();
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

$_REQUEST = array('task' => 'query', 'order' => 'asc');

require("$repoRoot/web/ajax/log.php");

$failures = 0;
$tests = 0;
function check($label, $cond) {
  global $failures, $tests;
  $tests++;
  if (!$cond) $failures++;
  printf("  %s - %s\n", $cond ? 'ok' : 'NOT OK', $label);
}

$rows = $GLOBALS['response']['rows'];
$component = function($rows, $index) {
  return $rows[$index]['Component'] ?? null;
};

check(
  'zmc_m37 links to watch when monitor 37 is viewable',
  $component($rows, 0) === '<a href="?view=watch&mid=37">zmc_m37</a>'
);
check(
  'zma_m5 stays plain text when monitor 5 is not viewable',
  $component($rows, 1) === 'zma_m5'
);
check(
  'zmc_m37<script> is escaped and not linked',
  $component($rows, 2) === 'zmc_m37&lt;script&gt;'
    && strpos((string)$component($rows, 2), '<a ') === false
);
check(
  'web_js stays plain text',
  $component($rows, 3) === 'web_js'
);

$GLOBALS['can_stream'] = false;
$GLOBALS['editable_monitors'][37] = true;
$editableRows = queryRequest()['rows'];
check(
  'zmc_m37 links to monitor when watch is not viewable but the user can edit',
  $component($editableRows, 0) === '<a href="?view=monitor&mid=37">zmc_m37</a>'
);

printf("%d tests, %d failures\n", $tests, $failures);
exit($failures ? 1 : 0);
