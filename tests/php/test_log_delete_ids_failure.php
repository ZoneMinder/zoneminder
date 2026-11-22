<?php
// Deleting selected log rows by id must report a failed DELETE to the browser
// instead of answering success. Usage: php test_log_delete_ids_failure.php [root]
$root = $argv[1] ?? __DIR__.'/../..';
$GLOBALS['err'] = null;
$GLOBALS['ok'] = false;
function dbQuery($sql, $params = null) { return null; } // simulated failure
function canEdit($a) { return true; }
function validHtmlStr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function ajaxError($m) { $GLOBALS['err'] = $m; }
function ajaxResponse($d = array()) { $GLOBALS['ok'] = true; }
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');
$_REQUEST = array('task' => 'delete', 'ids' => array('1', '2'));
require($root.'/web/ajax/log.php');
if ($GLOBALS['err'] !== null && !$GLOBALS['ok']) {
  echo "[PASS] failed delete by ids is reported: {$GLOBALS['err']}\n";
  exit(0);
}
echo "[FAIL] failed delete by ids was answered as success\n";
exit(1);
