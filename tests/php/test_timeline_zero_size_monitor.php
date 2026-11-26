<?php
// A monitor whose Width/Height are still 0 (dimensions not detected yet) must
// not make the timeline throw DivisionByZeroError when it has events.
namespace ZM {
  class Monitor {
    public static function find_one($filter) { return new Monitor(); }
    public function Width() { return 0; }
    public function Height() { return 0; }
  }
}
namespace {
  $src = file_get_contents(__DIR__.'/../../web/skins/classic/views/timeline.php');
  $start = strpos($src, 'if ( !isset($monitors[$event[\'MonitorId\']]) ) {');
  $end = strpos($src, '$currEventSlots = &$monEventSlots');
  if ($start === false || $end === false) { echo "FAIL markers not found\n"; exit(1); }
  $block = substr($src, $start, $end-$start);

  $monitors = array();
  $monEventSlots = array();
  $monFrameSlots = array();
  $event = array('MonitorId'=>3);
  try {
    eval($block);
    $ok = isset($monitors[3]) && isset($monEventSlots[3]) && isset($monFrameSlots[3]);
    echo ($ok ? 'ok   ' : 'FAIL ')."zero size monitor registered\n";
  } catch (\Throwable $e) {
    $ok = false;
    echo 'FAIL zero size monitor threw '.get_class($e).': '.$e->getMessage()."\n";
  }
  exit($ok ? 0 : 1);
}
