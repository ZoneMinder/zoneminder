<?php
// The Zones view loaded the monitor named by mid or mids[] without checking that
// the user may view it, so a user denied a monitor could list its zones. It now
// leaves such a monitor out, and shows the error view when none is left.
//
// Run as: php tests/php/test_zones_view_monitor_acl.php [root]

namespace ZM {
  class FakeMon {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function canView() { return $this->id == 5; }
  }
  class ZM_Object {
    public static function Objects_Indexed_By_Id($class, $params = null) {
      $out = array();
      foreach ($params['Id'] as $id) {
        if ($id == 5 or $id == 6) $out[$id] = new FakeMon($id);
      }
      return $out;
    }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  class ReachedRender extends Exception {}
  class StubUser {
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(); }
  }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }
  function translate($s) { return $s; }
  function xhtmlHeaders() { throw new ReachedRender(); }

  $cases = array(
    'mid5' => array(array('mid' => '5'), 'render'),
    'mid6' => array(array('mid' => '6'), 'error'),
    'mids56' => array(array('mids' => array('5', '6')), 'render'),
    'mids6' => array(array('mids' => array('6')), 'error'),
  );

  if (isset($argv[2])) {
    $_REQUEST = $cases[$argv[2]][0];
    $user = new StubUser();
    $view = 'zones';
    try {
      include $root.'/web/skins/classic/views/zones.php';
      echo $view;
    } catch (ReachedRender $e) {
      echo 'render';
    }
    exit(0);
  }

  $failures = 0;
  foreach ($cases as $name => $case) {
    $got = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg($name).' 2>&1');
    $got = trim((string)$got);
    if ($got === $case[1]) {
      echo "ok - $name gives {$case[1]}\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want {$case[1]})\n";
    }
  }
  exit($failures ? 1 : 0);
}
