<?php
// ajax/tags.php: tag names that differ only in case are treated as the same tag.
// createtag reuses the existing tag (preferring an exact match) instead of adding
// "Car" next to "car", and renametag refuses a name another tag already has in any case.
//
// Run as: php tests/php/test_ajax_tags_case_insensitive.php [repo root]

$root = $argv[1] ?? __DIR__.'/../..';
class Done extends Exception {}
$GLOBALS['tags'] = array(
  array('Id'=>1, 'Name'=>'car'),
  array('Id'=>2, 'Name'=>'unused'),
);
$GLOBALS['queries'] = array();
function canView($a) { return true; }
function canEdit($a) { return true; }
function visibleMonitor($m) { return true; }
function validHtmlStr($s) { return $s; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function ajaxError($m) { $GLOBALS['out'] = array('error'=>$m); throw new Done(); }
function ajaxResponse($r=false) { $GLOBALS['out'] = array('ok'=>$r); throw new Done(); }
// A tiny model of the Tags table: '=' compares exactly (utf8mb4_bin), LOWER() ignores case.
function match_name($sql, $name, $wanted) {
  if (strpos($sql, 'LOWER(Name) = LOWER(?)') !== false) return strtolower($name) === strtolower($wanted);
  return $name === $wanted;
}
function dbFetchAll($sql, $col=null, $params=array()) {
  if (strpos($sql, 'FROM Tags WHERE') !== false and strpos($sql, 'Name') !== false) {
    $rows = array_values(array_filter($GLOBALS['tags'], function($t) use ($sql, $params) { return match_name($sql, $t['Name'], $params[0]); }));
    return array_slice($rows, 0, 1);
  }
  if (strpos($sql, 'FROM Tags WHERE Id = ?') !== false) {
    return array(array('Id'=>$params[0], 'Name'=>$GLOBALS['last_insert']));
  }
  return array();
}
function dbFetchOne($sql, $col=null, $params=array()) {
  if (strpos($sql, 'FROM Tags WHERE Id = ?') !== false) {
    foreach ($GLOBALS['tags'] as $t) if ($t['Id'] == $params[0]) return array('Id'=>$t['Id']);
    return false;
  }
  if (strpos($sql, 'AND Id != ?') !== false) {
    foreach ($GLOBALS['tags'] as $t) if (match_name($sql, $t['Name'], $params[0]) and $t['Id'] != $params[1]) return array('Id'=>$t['Id']);
    return false;
  }
  return false;
}
function dbQuery($sql, $params=array()) {
  $GLOBALS['queries'][] = $sql;
  if (strpos($sql, 'INSERT INTO Tags') !== false) $GLOBALS['last_insert'] = $params[0];
  return true;
}
function dbInsertId() { return 99; }
$user = new class { function Id() { return 1; } };

function run($request) {
  global $root, $user;
  $GLOBALS['out'] = null;
  $GLOBALS['queries'] = array();
  $_REQUEST = $request;
  try {
    include $root.'/web/ajax/tags.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$out = run(array('action'=>'createtag', 'tname'=>'Car'));
check('creating "Car" reuses the existing "car"', $out['ok']['response'][0]['Id'] ?? null, 1);
check('creating "Car" inserts nothing', count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'INSERT') !== false; })), 0);

$out = run(array('action'=>'createtag', 'tname'=>'truck'));
check('a new name is still created', $out['ok']['response'][0]['Id'] ?? null, 99);

$out = run(array('action'=>'renametag', 'tid'=>'2', 'tname'=>'CAR'));
check('renaming to "CAR" when "car" exists is refused', isset($out['error']) and !count($GLOBALS['queries']), true);

$out = run(array('action'=>'renametag', 'tid'=>'1', 'tname'=>'Car'));
check('a tag can change the case of its own name', isset($out['ok']), true);

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
