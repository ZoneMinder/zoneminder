<?php
namespace ZM {
  class Event {
    private $id;
    function __construct($id) { $this->id = $id; }
    function canView() { return $this->id == 5; }
  }
}

namespace {
  function canView($p) { return true; }
  function validInt($v) { return (int)$v; }
  function translate($s) { return $s; }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function getStatsTableHTML($e, $f, $r = '') { return 'STATS-'.$e; }
  function xhtmlFooter() {}

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
  $GLOBALS['root'] = $root;

  function run($eid) {
    global $root, $view;
    $_REQUEST = array('eid' => $eid, 'fid' => '1');
    $view = 'stats';
    ob_start();
    include $root.'/web/skins/classic/views/stats.php';
    $output = ob_get_clean();
    return array($view, $output);
  }

  $failed = false;

  list($view, $output) = run(5);
  if ($view === 'stats' && strpos($output, 'STATS-5') !== false) {
    echo "ok - eid 5 allowed\n";
  } else {
    echo "FAIL - eid 5 allowed (got view=$view output=".var_export($output, true).")\n";
    $failed = true;
  }

  list($view, $output) = run(6);
  if ($view === 'error' && strpos($output, 'STATS-6') === false) {
    echo "ok - eid 6 denied\n";
  } else {
    echo "FAIL - eid 6 denied (got view=$view output=".var_export($output, true).")\n";
    $failed = true;
  }

  exit($failed ? 1 : 0);
}
