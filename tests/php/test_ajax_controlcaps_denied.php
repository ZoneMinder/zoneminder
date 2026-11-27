<?php
// web/ajax/controlcaps.php must deny a request from a user without Control
// permissions by calling ajaxError(), not by returning silently. A silent
// return leaves the AJAX client waiting and reports nothing to the user.
//
// Run as: php tests/php/test_ajax_controlcaps_denied.php [repo_root]

namespace ZM {
  function Warning($m) {}
}

namespace {
  class StopRequest extends Exception {}

  $root = $argv[1] ?? __DIR__.'/../..';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n";
    }
  }

  function canEdit($area) { return false; }

  $GLOBALS['ajax_error_message'] = null;
  function ajaxError($m) {
    $GLOBALS['ajax_error_message'] = $m;
    throw new StopRequest($m);
  }
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function dbQuery($sql, $params = array()) { throw new StopRequest('db'); }
  function dbLastError() { return ''; }
  function validCardinal($v) { return $v; }

  $_REQUEST = array('action' => 'delete', 'mcid' => 1);
  try {
    include $root.'/web/ajax/controlcaps.php';
  } catch (StopRequest $e) {
    // ajaxError() records the message and throws; the silent return does not.
  }

  $msg = $GLOBALS['ajax_error_message'];
  check('denied controlcaps request calls ajaxError', !is_null($msg), true);
  check('ajaxError message is non-empty', is_string($msg) && $msg !== '', true);
  if (is_string($msg) && $msg !== '') {
    echo "  message: $msg\n";
  }

  echo "\n".($failures ? "FAILURES: $failures\n" : "All passed\n");
  exit($failures ? 1 : 0);
}
