<?php
// The frame view's First/Prev/Next/Last links must point at frames that exist.
// Runs the navigation and link code taken from frame.php against a stubbed,
// sparse Frames table.
//
// Run as: php tests/php/test_frame_navigation.php

namespace ZM {
  class Frame {
    private $id;
    public function __construct($f) { $this->id = $f ? $f['FrameId'] : null; }
    public function FrameId() { return $this->id; }
  }
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "  ok $name\n"; return; }
    $failures++;
    echo "  FAIL $name\n    got:  ".var_export($got, true)."\n    want: ".var_export($want, true)."\n";
  }
  function translate($s) { return $s; }

  $frames = array();
  function dbFetchOne($sql, $col, $args) {
    global $frames;
    $ids = $frames;
    if (preg_match('/FrameId < \?/', $sql)) $ids = array_filter($ids, function($i) use ($args) { return $i < $args[1]; });
    if (preg_match('/FrameId > \?/', $sql)) $ids = array_filter($ids, function($i) use ($args) { return $i > $args[1]; });
    if (!count($ids)) return null;
    return preg_match('/MIN\(/', $sql) ? min($ids) : max($ids);
  }

  $src = file_get_contents(__DIR__.'/../../web/skins/classic/views/frame.php');
  if (!preg_match('/^(.*?)\$Frame = new ZM\\\\Frame\(\$frame\);\n(.*?)\n\$alarmFrame/s', $src, $navSrc) or
      !preg_match('/\$frame_url_base = .*?<p id="controls">(.*?)<\/p>/s', $src, $ctl)) {
    echo "FAIL - could not find the navigation code in frame.php\n";
    exit(1);
  }

  function links($current, $fid, $ids) {
    global $navSrc, $ctl, $frames;
    $frames = $ids;
    $eid = 7;
    $Frame = new \ZM\Frame(array('FrameId'=>$current));
    $Event = new class { public function Frames() { global $frames; return count($frames) ? 99 : 0; } };
    $frame_url_base = 'U';
    $code = $navSrc[2];
    if (function_exists('frameNavigation')) $code = preg_replace('/^function frameNavigation.*?\n\}\n/ms', '', $code);
    eval($code);
    ob_start();
    eval('?>'.$ctl[1]);
    $html = ob_get_clean();
    $out = array();
    foreach (array('first','prev','next','last') as $l) {
      preg_match('/id="'.$l.'Link" (href="U(\d+)"|[^>]*disabled)/', $html, $m);
      $out[$l] = isset($m[2]) ? (int)$m[2] : null;
    }
    return $out;
  }

  check('dense middle frame', links(3, 3, array(1,2,3,4,5)), array('first'=>1,'prev'=>2,'next'=>4,'last'=>5));
  check('dense first frame', links(1, 1, array(1,2,3)), array('first'=>null,'prev'=>null,'next'=>2,'last'=>3));
  check('dense last frame', links(3, 3, array(1,2,3)), array('first'=>1,'prev'=>2,'next'=>null,'last'=>null));
  check('sparse frames skip the gaps', links(10, 10, array(1,10,20)), array('first'=>1,'prev'=>1,'next'=>20,'last'=>20));
  check('frames starting after 1: no prev link to nothing', links(5, 5, array(5,6,7)), array('first'=>null,'prev'=>null,'next'=>6,'last'=>7));
  check('default view (fid=0) of a later frame uses that frame', links(5, 0, array(1,5,9)), array('first'=>1,'prev'=>1,'next'=>9,'last'=>9));
  check('single frame has no links', links(1, 1, array(1)), array('first'=>null,'prev'=>null,'next'=>null,'last'=>null));

  exit($failures ? 1 : 0);
}
