<?php

namespace ZM {
  function Warning($m) {}
  class Monitor {
    private $id;
    static function find_one($w) {
      $m = new Monitor();
      $m->id = $w['Id'];
      return $m;
    }
    function canView() {
      return $this->id == 5;
    }
  }
}

namespace {
  class Reached extends Exception {}

  function canView($p) { return true; }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function getZmuCommand($a) { throw new Reached(); }

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
  $file = $root.'/web/ajax/modals/settings.php';

  function run($mid) {
    global $file;
    $_REQUEST = array('mid' => $mid);
    ob_start();
    $reached = false;
    try {
      include $file;
    } catch (Reached $e) {
      $reached = true;
    }
    $output = ob_get_clean();
    return array($reached, $output);
  }

  $fail = 0;

  list($reached, $output) = run(5);
  if ($reached === true) {
    echo "ok - mid 5 reaches getZmuCommand\n";
  } else {
    echo "FAIL - mid 5 reaches getZmuCommand (got reached=".var_export($reached, true).")\n";
    $fail = 1;
  }

  list($reached, $output) = run(6);
  if ($reached === false) {
    echo "ok - mid 6 does not reach getZmuCommand\n";
  } else {
    echo "FAIL - mid 6 does not reach getZmuCommand (got reached=".var_export($reached, true).")\n";
    $fail = 1;
  }
  if ($output === '') {
    echo "ok - mid 6 prints nothing\n";
  } else {
    echo "FAIL - mid 6 prints nothing (got ".var_export($output, true).")\n";
    $fail = 1;
  }

  exit($fail);
}
