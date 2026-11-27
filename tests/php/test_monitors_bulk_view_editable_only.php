<?php
// The bulk monitor edit view loaded every requested monitor by id, including deleted
// monitors and monitors the user cannot edit, and then showed their settings. It must
// keep only monitors that are not deleted and that canEdit('Monitors', $id) allows, and
// fall back to the error page when none are left.
//
// Run as: php tests/php/test_monitors_bulk_view_editable_only.php [path to monitors.php]
//
// Each case runs in a child process because the view declares functions (bulkOptions,
// bulkSelected); including it twice in one process would fatal on redeclaration instead
// of reporting a behaviour failure.

namespace ZM {
  class Monitor {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function Deleted() { return $this->id == 3; }
    function __call($name, $args) { return ''; }
    static function find($q) {
      $out = array();
      foreach ($q['Id'] as $id) $out[] = new Monitor($id);
      return $out;
    }
  }
  class Server {
    static function find() { return array(); }
  }
  class Storage {
    static function find() { return array(); }
  }
}

namespace {
  function canEdit($a, $id = null) {
    if ($a != 'Monitors') return true;
    return $id != 2;
  }
  function validCardinal($v) { return (int)$v; }
  function translate($s) { return $s; }
  function xhtmlHeaders($f = null, $t = null) { throw new Exception('STOP'); }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $path = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views/monitors.php';
  $case = isset($argv[2]) ? $argv[2] : null;

  if ($case === null) {
    // Parent: run every case in its own process and aggregate the results.
    $cases = array('A', 'B', 'C');
    $failed = false;
    foreach ($cases as $c) {
      $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '
           .escapeshellarg($path).' '.escapeshellarg($c);
      $out = array();
      exec($cmd, $out, $rc);
      echo "== case $c ==\n".implode("\n", $out)."\n";
      if ($rc !== 0) $failed = true;
    }
    echo $failed ? "\nFAILED\n" : "\nall passed\n";
    exit($failed ? 1 : 0);
  }

  function run_case($path, $case) {
    global $monitors, $view, $failures;
    $mids = array(
      'A' => array(1, 2, 3),
      'B' => array(2),
      'C' => array(3),
    );
    $_REQUEST = array('mids' => $mids[$case]);
    $monitors = null;
    $view = 'monitors';

    ob_start();
    try {
      include $path;
    } catch (Throwable $e) {
      // The view calls xhtmlHeaders() right after loading the monitors; the stub throws
      // STOP so the form markup is never rendered.
    }
    ob_end_clean();

    if ($case === 'A') {
      $ids = is_array($monitors) ? array_map(function($m) { return $m->Id(); }, $monitors) : array();
      check('keeps only the monitor the user may edit', $ids, array(1));
      check('does not fall back to the error page when an editable monitor remains', $view, 'monitors');
    } else if ($case === 'B') {
      check('falls back to the error page when the only monitor is not editable', $view, 'error');
    } else {
      check('falls back to the error page when the only monitor is deleted', $view, 'error');
    }

    exit($failures ? 1 : 0);
  }

  run_case($path, $case);
}
