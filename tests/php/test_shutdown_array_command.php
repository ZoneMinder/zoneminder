<?php

namespace ZM {
  function Warning($m) {}
  function Debug($m) {}
}

namespace {
  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "  ok $name\n";
    } else {
      $failures++;
      echo "  FAIL $name\n";
      echo '    got:  '.var_export($got, true)."\n";
      echo '    want: '.var_export($want, true)."\n";
    }
  }

  define('ZM_PATH_SHUTDOWN', '/sbin/shutdown');
  $execs = array();
  $errors = array();
  function canEdit($p) { return true; }
  function stub_exec($cmd, &$out = null, &$rc = null) { global $execs; $execs[] = $cmd; $out = array(); $rc = 0; }
  function ajaxError($m) { global $errors; $errors[] = $m; }
  function ajaxResponse($d) {}

  function runShutdown() {
    $src = file_get_contents(__DIR__.'/../../web/ajax/shutdown.php');
    $src = preg_replace('/\bexec\(/', 'stub_exec(', $src);
    eval('?>'.$src);
  }

  set_error_handler(function($no, $str) { throw new ErrorException($str); });

  $_REQUEST = array('command'=>array('shutdown'));
  $threw = '';
  try {
    runShutdown();
  } catch (ErrorException $e) {
    $threw = $e->getMessage();
  }
  check('array command raises no notice', $threw, '');
  check('array command runs nothing', count($execs), 0);

  $execs = array();
  $_REQUEST = array('command'=>'restart', 'when'=>'1min');
  runShutdown();
  check('restart in a minute', $execs, array('sudo -n /sbin/shutdown -r +1 2>&1'));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
