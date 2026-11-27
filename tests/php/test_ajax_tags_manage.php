<?php
// ajax/tags.php: gettags lists tags with event counts for events on monitors the
// user can see, renametag and deletetag need Events edit, a rename may not collide
// with another tag and only a tag on no event can be deleted. See #4808.
//
// Run as: php tests/php/test_ajax_tags_manage.php

class Done extends Exception {}
$GLOBALS['canEdit'] = true;
$GLOBALS['queries'] = array();
$GLOBALS['tags'] = array(
  array('Id'=>1, 'Name'=>'car', 'CreateDate'=>null, 'LastAssignedDate'=>null),
  array('Id'=>2, 'Name'=>'unused', 'CreateDate'=>null, 'LastAssignedDate'=>null),
  array('Id'=>3, 'Name'=>'secret', 'CreateDate'=>null, 'LastAssignedDate'=>null),
);
// TagId, MonitorId, Events
$GLOBALS['usage'] = array(
  array('TagId'=>1, 'MonitorId'=>1, 'Events'=>3),
  array('TagId'=>1, 'MonitorId'=>2, 'Events'=>4),
  array('TagId'=>3, 'MonitorId'=>2, 'Events'=>5),
);
function canView($a) { return true; }
function canEdit($a) { return $GLOBALS['canEdit']; }
function visibleMonitor($m) { return $m == 1; }
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
function dbQuery($sql, $params=array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }
$user = new class { function Id() { return 1; } };

function run($request) {
  $GLOBALS['out'] = null;
  $GLOBALS['queries'] = array();
  $_REQUEST = $request;
  try {
    include __DIR__.'/../../web/ajax/tags.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$out = run(array('action'=>'gettags'));
$rows = isset($out['ok']['response']) ? $out['ok']['response'] : array();
check('gettags counts only visible events', array_column($rows, 'Events', 'Name'), array('car'=>3, 'unused'=>0));
check('gettags hides a tag used only on hidden monitors', in_array('secret', array_column($rows, 'Name')), false);

$out = run(array('action'=>'renametag', 'tid'=>'1', 'tname'=>'auto'));
check('rename refused for a tag on events the user cannot see', isset($out['error']) && !count($GLOBALS['queries']), true);

$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>' fresh '));
check('rename updates the tag with the trimmed name', $GLOBALS['queries'], array(array('UPDATE Tags SET Name = ? WHERE Id = ?', array('fresh', '2'))));
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>'car'));
check('rename onto an existing name is refused', isset($out['error']), true);
check('refused rename writes nothing', $GLOBALS['queries'], array());
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>''));
check('rename to an empty name is refused', isset($out['error']), true);
$out = run(array('action'=>'renametag', 'tid'=>'99', 'tname'=>'x'));
check('rename of a missing tag is refused', isset($out['error']), true);
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>str_repeat('a', 65)));
check('rename to an over-long name is refused', isset($out['error']), true);

$out = run(array('action'=>'deletetag', 'tid'=>'2'));
check('delete removes an unused tag', $GLOBALS['queries'], array(array('DELETE FROM Tags WHERE Id = ?', array('2'))));
$out = run(array('action'=>'deletetag', 'tid'=>'1'));
check('delete of a tag on events is refused', isset($out['error']), true);
$out = run(array('action'=>'deletetag', 'tid'=>'3'));
check('delete of a tag on hidden events is refused', isset($out['error']), true);
check('refused deletes write nothing', $GLOBALS['queries'], array());

$GLOBALS['canEdit'] = false;
$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>'fresh'));
check('rename needs Events edit', isset($out['error']) and $GLOBALS['queries'] === array(), true);
$out = run(array('action'=>'deletetag', 'tid'=>'2'));
check('delete needs Events edit', isset($out['error']) and $GLOBALS['queries'] === array(), true);
$out = run(array('action'=>'gettags'));
check('gettags only needs view', isset($out['ok']), true);

exit($failures ? 1 : 0);
