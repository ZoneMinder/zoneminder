<?php
// The groups view's setgroup action with no group id clears the zmGroup cookie.
// zm_setcookie() takes an options array as its third argument; given a bare
// expiry time it tried to set 'path' on an int, which is an Error on PHP 8, so
// the request died instead of clearing the cookie.
//
// Runs the real action file and session.php from a source checkout:
// php tests/php/test_groups_setgroup_clear.php

namespace ZM {
  function Warning($s) {}
}

namespace {
  function canView($p) { return true; }
  function canEdit($p) { return false; }
  function validInt($v) { return preg_replace('/\D/', '', $v); }

  require_once __DIR__.'/../../web/includes/session.php';

  $failures = 0;
  $passes = 0;

  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  function setgroup($request) {
    $_REQUEST = $request;
    $action = 'setgroup';
    $refreshParent = false;
    try {
      ob_start();
      include __DIR__.'/../../web/includes/actions/groups.php';
      ob_end_clean();
    } catch (\Error $e) {
      ob_end_clean();
      return get_class($e).': '.$e->getMessage();
    }
    return $refreshParent;
  }

  check('setgroup with a group id', setgroup(array('gid' => '3')), true);
  check('setgroup without a group id clears the cookie', setgroup(array()), true);
  check('setgroup with an empty group id clears the cookie', setgroup(array('gid' => '')), true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
