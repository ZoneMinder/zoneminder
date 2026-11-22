<?php
// The classic $scales list offers 'fit_to_width' and 'NNNpx' as well as
// percentages. The frame view must not read '480px' as 480 percent (a 1920px
// image became 9216px wide), and the select must keep the chosen key.
//
// Run as: php tests/php/test_frame_view_scale.php [web-root]   (default: this checkout's web/)

namespace ZM {
  class Event {
    public function Width() { return 1920; }
    public function Height() { return 1080; }
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
  define('SCALE_BASE', 100);
  function validNum($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/[^\d.-]/', '', $input);
  }

  $web = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
  $fns = file_get_contents($web.'/includes/functions.php');
  if (!preg_match('/^function reScale\(.*?^\}\n/ms', $fns, $m)) { echo "FAIL - reScale not found\n"; exit(1); }
  eval($m[0]);

  $cfg = file_get_contents($web.'/skins/classic/includes/config.php');
  if (!preg_match('/^\$scales = array\(.*?^\);\n/ms', $cfg, $m)) { echo "FAIL - \$scales not found\n"; exit(1); }
  function translate($s) { return $s; }
  eval($m[0]);

  $src = file_get_contents($web.'/skins/classic/views/frame.php');
  $start = strpos($src, "if (isset(\$_REQUEST['scale'])) {");
  $end = strpos($src, '$imageData = $Event->getImageSrc(');
  if ($start === false || $end === false) { echo "FAIL - scale code not found in frame.php\n"; exit(1); }
  $chunk = substr($src, $start, $end - $start);

  function run($request, $default = 0) {
    global $chunk, $scales;
    $_REQUEST = $request;
    $_COOKIE = array();
    $Event = new \ZM\Event();
    $Monitor = new class($default) {
      private $d;
      public function __construct($d) { $this->d = $d; }
      public function Id() { return 1; }
      public function DefaultScale() { return $this->d; }
    };
    eval($chunk);
    return array(
      'width' => reScale($Event->Width(), $scale),
      'height' => reScale($Event->Height(), $scale),
      'selected' => isset($scaleSelected) ? $scaleSelected : null,
    );
  }

  echo "480px caps the width:\n";
  $r = run(array('scale' => '480px'));
  check('width is 480', $r['width'], 480);
  check('height keeps the aspect ratio', $r['height'], 270);
  check('select keeps 480px', $r['selected'], '480px');

  $r = run(array('scale' => '1600px'));
  check('1600px stays within 1600 and close to it', $r['width'] <= 1600 && $r['width'] > 1550, true);

  echo "fit_to_width is sized by frame.js, not read as a percentage:\n";
  $r = run(array('scale' => 'fit_to_width'));
  check('server leaves the full size', $r['width'], 1920);
  check('select keeps fit_to_width', $r['selected'], 'fit_to_width');

  echo "percentages are unchanged:\n";
  $r = run(array('scale' => '50'));
  check('50 halves the width', $r['width'], 960);
  check('select keeps 50', $r['selected'], '50');
  $r = run(array('scale' => '0'));
  check('0 (Auto) is the full size', $r['width'], 1920);
  $r = run(array(), '480px');
  check('monitor default 480px caps the width', $r['width'], 480);

  echo "the select is given the raw selected value:\n";
  check('htmlSelect uses $scaleSelected', strpos($src, "htmlSelect('scale', \$scales, \$scaleSelected") !== false, true);

  exit($failures ? 1 : 0);
}
