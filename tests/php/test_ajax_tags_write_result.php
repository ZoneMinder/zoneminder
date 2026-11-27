<?php
// ajax/tags.php: renametag and deletetag must not answer success when the write
// query fails. See #4808.
//
// Run as: php tests/php/test_ajax_tags_write_result.php [repo-root]

class Done extends Exception {}
$GLOBALS['root'] = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$GLOBALS['canEdit'] = true;
$GLOBALS['dbResult'] = true;
$GLOBALS['queries'] = array();
$GLOBALS['tags'] = array(
  array('Id'=>2, 'Name'=>'unused', 'CreateDate'=>null, 'LastAssignedDate'=>null),
);
// TagId, MonitorId, Events
$GLOBALS['usage'] = array();
function canView($a) { return true; }
function canEdit($a) { return $GLOBALS['canEdit']; }
function visibleMonitor($m) { return true; }
function validHtmlStr($s) { return $s; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function ajaxError($m) { $GLOBALS['out'] = array('error'=>$m); throw new Done(); }
function ajaxResponse($r=false) { $GLOBALS['out'] = array('ok'=>$r); throw new Done(); }
function dbFetchAll($sql, $col=null, $params=array()) {
  if (strpos($sql, 'WHERE ET.TagId = ?') !== false) {
    return array_values(array_filter($GLOBALS['usage'], function($u) use ($params) { return $u['TagId'] == $params[0]; }));
  }
  if (strpos($sql, 'FROM Events_Tags AS ET INNER JOIN Events') !== false) return $GLOBALS['usage'];
  if (strpos($sql, 'FROM Tags ORDER BY Name') !== false) return $GLOBALS['tags'];
  return array();
}
function dbFetchOne($sql, $col=null, $params=array()) {
  if (strpos($sql, 'FROM Tags WHERE Id = ?') !== false) {
    foreach ($GLOBALS['tags'] as $t) if ($t['Id'] == $params[0]) return array('Id'=>$t['Id']);
    return false;
  }
  if (strpos($sql, 'LOWER(Name) = LOWER(?) AND Id != ?') !== false) {
    foreach ($GLOBALS['tags'] as $t) if (strtolower($t['Name']) === strtolower($params[0]) and $t['Id'] != $params[1]) return array('Id'=>$t['Id']);
    return false;
  }
  if (strpos($sql, 'FROM Events_Tags WHERE TagId') !== false) {
    foreach ($GLOBALS['usage'] as $u) if ($u['TagId'] == $params[0]) return array('TagId'=>$u['TagId']);
    return false;
  }
  return false;
}
function dbQuery($sql, $params=array()) { $GLOBALS['queries'][] = array($sql, $params); return $GLOBALS['dbResult']; }
$user = new class { function Id() { return 1; } };

function run($request) {
  $GLOBALS['out'] = null;
  $_REQUEST = $request;
  try {
    include $GLOBALS['root'].'/web/ajax/tags.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$GLOBALS['dbResult'] = true;
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>'fresh'));
check('rename answers success when the update succeeds', isset($out['ok']), true);

$GLOBALS['dbResult'] = null;
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>'fresh'));
check('rename answers an error when the update fails', isset($out['error']) and !isset($out['ok']), true);

$GLOBALS['dbResult'] = true;
$out = run(array('action'=>'deletetag', 'tid'=>'2'));
check('delete answers success when the delete succeeds', isset($out['ok']), true);

$GLOBALS['dbResult'] = null;
$out = run(array('action'=>'deletetag', 'tid'=>'2'));
check('delete answers an error when the delete fails', isset($out['error']) and !isset($out['ok']), true);

exit($failures ? 1 : 0);
