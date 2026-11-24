<?php
// Saving a server whose id no longer exists (deleted in another tab) ran an UPDATE that
// matched no row, then audited the update, refreshed the parent and redirected to the server
// list as if the save had worked, so the edit was lost without a word. It has to refuse and
// report that the server is gone, while an existing server and a new server still save.
//
// Runs the real web/includes/actions/server.php and getFormChanges() from
// web/includes/functions.php against a stubbed database.
//
// php tests/php/test_server_save_missing_server.php [root]   (root defaults to the repo)

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function AuditAction($a, $t, $id, $d) { $GLOBALS['audits'][] = array($a, $t, $id); }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

  // getFormChanges() from the real functions.php, which cannot be included whole.
  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if (!preg_match('/^function getFormChanges\(.*?^}$/ms', $src, $m)) {
    echo "FAIL getFormChanges not found in functions.php\n";
    exit(1);
  }
  eval($m[0]);
  function dbEscape($s) { return "'".addslashes($s)."'"; }

  function canEdit($a) { return true; }
  // Server 7 is the only one that exists.
  function dbFetchOne($sql, $col = null, $args = array()) {
    return ($args == array('7')) ? array('Id' => 7, 'Name' => 'Old', 'Hostname' => 'old') : false;
  }
  function dbQuery($sql, $args = array()) { $GLOBALS['queries'][] = $sql; return true; }
  function dbInsertId() { return 55; }
  function dbLastError() { return ''; }

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($request) {
    global $root, $action, $error_message, $redirect, $refreshParent, $view;
    $GLOBALS['queries'] = array();
    $GLOBALS['audits'] = array();
    $action = 'save';
    $error_message = '';
    $redirect = null;
    $refreshParent = false;
    $view = 'server';
    $_REQUEST = $request + array('newServer' => array('Name' => 'New', 'Hostname' => 'new'));
    include $root.'/web/includes/actions/server.php';
    return array($error_message, $redirect, $refreshParent);
  }

  list($err, $redir, $refresh) = run(array('id' => '9'));
  check('a deleted server runs no query', $GLOBALS['queries'], array());
  check('a deleted server is not audited', $GLOBALS['audits'], array());
  check('a deleted server reports that it is gone', $err, 'Server not found<br/>');
  check('a deleted server does not redirect', $redir, null);
  check('a deleted server does not refresh the parent', $refresh, false);

  list($err, $redir, $refresh) = run(array('id' => '7'));
  check('an existing server is updated', count($GLOBALS['queries']), 1);
  check('an existing server is audited as an update', $GLOBALS['audits'], array(array('update', 'server', '7')));
  check('an existing server reports no error', $err, '');
  check('an existing server goes back to the list', $redir, '?view=options&tab=servers');

  list($err, $redir, $refresh) = run(array());
  check('a new server is inserted', count($GLOBALS['queries']), 1);
  check('a new server is audited as a create', $GLOBALS['audits'], array(array('create', 'server', 55)));
  check('a new server reports no error', $err, '');

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
