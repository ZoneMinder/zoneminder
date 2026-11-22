<?php
// Deleting servers, storage areas and roles, and resetting the menu (Options), ignored the
// result of their queries and refreshed or redirected as if it had worked. A failed query
// is now reported and the page is not refreshed or redirected as a success.
//
// Run as: php tests/php/test_options_delete_results.php [root]   (root defaults to the repo)

function canEdit($a) { return true; }
function dbLastError() { return 'db down'; }
function validHtmlStr($v) { return htmlspecialchars((string)$v); }
function validCardinal($v) { return (int)$v; }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = trim($sql);
  return $GLOBALS['dbResult'];
}
eval('namespace ZM;
function Warning($m) {} function Error($m) {} function Debug($m) {}
function AuditAction($a, $o, $id, $x) { $GLOBALS["audits"][] = "$a $o $id"; }
class Storage {
  static function find_one($f) { return new Storage($f["Id"]); }
  private $id; function __construct($id) { $this->id = $id; }
  function Id() { return $this->id; } function Name() { return "S".$this->id; }
  function EventCount() { return 0; } function MonitorCount() { return 0; }
}
class Server { static function InUseSummary($id) { return ""; } }');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
chdir($root.'/web');
if (!file_exists('includes/config_values.php')) { echo "no config_values.php in $root\n"; exit(2); }

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

class FakeUser { function Id() { return 1; } }

function run($request, $dbResult) {
  global $error_message, $redirect, $refreshParent, $action, $user, $root;
  $error_message = null;
  $redirect = null;
  $refreshParent = false;
  $action = $request['action'];
  $user = new FakeUser();
  $GLOBALS['queries'] = array();
  $GLOBALS['audits'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = $request;
  include $root.'/web/includes/actions/options.php';
  return array((string)$error_message, $redirect, $refreshParent, $GLOBALS['audits']);
}

$cases = array(
  'server'  => array(array('action'=>'delete', 'object'=>'server', 'markIds'=>array('3')), 'refresh', 'Error deleting server 3', 'delete server 3'),
  'storage' => array(array('action'=>'delete', 'object'=>'storage', 'markIds'=>array('3')), 'refresh', 'Error deleting storage area', 'delete storage 3'),
  'role'    => array(array('action'=>'delete', 'object'=>'role', 'markRids'=>array('3')), '?view=options&tab=roles', 'Error deleting role 3', 'delete role 3'),
);
foreach ($cases as $name => $c) {
  list($req, $ok, $msg, $audit) = $c;
  list($err, $redir, $refresh, $audits) = run($req, true);
  check("$name delete that works reports nothing", $err, '');
  check("$name delete that works is audited", $audits, array($audit));
  if ($ok == 'refresh') check("$name delete that works refreshes", $refresh, true);
  else check("$name delete that works redirects", $redir, $ok);

  list($err, $redir, $refresh, $audits) = run($req, null);
  check("$name failed delete is reported", strpos($err, $msg) !== false && strpos($err, 'db down') !== false, true);
  check("$name failed delete is not audited", $audits, array());
  check("$name failed delete does not refresh or redirect", array($refresh, $redir), array(false, null));
}

$req = array('action'=>'resetmenu');
list($err, $redir, $refresh, $audits) = run($req, true);
check('menu reset that works reports nothing', $err, '');
check('menu reset that works redirects', $redir, '?view=options&tab=menu');
check('menu reset runs delete then insert', count($GLOBALS['queries']), 2);

list($err, $redir, $refresh, $audits) = run($req, null);
check('failed menu reset is reported', strpos($err, 'reset menu') !== false && strpos($err, 'db down') !== false, true);
check('failed menu reset does not redirect', $redir, null);
check('failed menu delete skips the insert', count($GLOBALS['queries']), 1);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
