<?php
// ajax/tags.php createtag refuses a name over the 64 characters of Tags.Name, as renametag does,
// rather than letting the INSERT fail or truncate.
//
// Run as: php tests/php/test_ajax_tags_create_length.php [root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
class Done extends Exception {}
$GLOBALS['queries'] = array();
function canView($a) { return true; }
function canEdit($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = array('error'=>$m); throw new Done(); }
function ajaxResponse($r=false) { $GLOBALS['out'] = array('ok'=>$r); throw new Done(); }
function dbFetchAll($sql, $col=null, $params=array()) { return array(); }
function dbQuery($sql, $params=array()) { $GLOBALS['queries'][] = $params; return true; }
function dbInsertId() { return 1; }
$user = new class { function Id() { return 1; } };

function run($name) {
  $GLOBALS['out'] = null;
  $GLOBALS['queries'] = array();
  $_REQUEST = array('action'=>'createtag', 'tname'=>$name);
  global $root, $user;
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

$out = run(str_repeat('a', 65));
check('65 characters refused', isset($out['error']) && !count($GLOBALS['queries']), true);
$out = run(str_repeat("\u{e9}", 65));
check('65 multibyte characters refused', isset($out['error']) && !count($GLOBALS['queries']), true);
$out = run(str_repeat('a', 64));
check('64 characters created', isset($out['ok']) && count($GLOBALS['queries']) == 1, true);
$out = run(str_repeat("\u{e9}", 64));
check('64 multibyte characters created', isset($out['ok']) && count($GLOBALS['queries']) == 1, true);
$out = run('   ');
check('blank name refused', isset($out['error']) && !count($GLOBALS['queries']), true);

exit($failures ? 1 : 0);
