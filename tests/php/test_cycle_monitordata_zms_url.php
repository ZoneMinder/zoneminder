<?php
// views/js/cycle.js.php left url_to_zms out of monitorData. MonitorStream builds
// every zms address from it, so in the cycle view showing a message on the
// stream (setSrcInfoBlock) threw "Cannot read properties of undefined
// (reading 'replace')".
//
// Run: php tests/php/test_cycle_monitordata_zms_url.php   (from the repo root)

namespace {
  $passed = 0;
  $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label\n";
      echo "       got:  ".var_export($got, true)."\n";
      echo "       want: ".var_export($want, true)."\n";
    }
  }

  define('ZM_WEB_REFRESH_CYCLE', 10);
  define('ZM_WEB_REFRESH_STATUS', 5);
  function validJsStr($v) { return addslashes($v); }

  class FakeMonitor {
    public function Id() { return 7; }
    public function Name() { return 'Front'; }
    public function connKey() { return 123; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
    public function UrlToIndex() { return 'https://zm.example/zm/index.php'; }
    public function UrlToZMS() { return 'https://zm.example/cgi-bin/nph-zms?monitor=7'; }
    public function Type() { return 'Ffmpeg'; }
    public function Capturing() { return 'Always'; }
    public function Refresh() { return ''; }
    public function RTSP2WebEnabled() { return 0; }
    public function RTSP2WebType() { return 'HLS'; }
    public function RTSPServer() { return false; }
    public function StreamChannel() { return 'Restream'; }
    public function JanusEnabled() { return 0; }
    public function Janus_Pin() { return ''; }
  }

  // Same scope as the real include in cycle.php.
  $monIdx = 0;
  $nextMid = 7;
  $options = array('mode' => 'stream');
  $monitors = array(new FakeMonitor());
  ob_start();
  require __DIR__.'/../../web/skins/classic/views/js/cycle.js.php';
  $js = ob_get_clean();

  check('monitorData carries the zms url',
    preg_match("/'url_to_zms': 'https:\/\/zm\.example\/cgi-bin\/nph-zms\?monitor=7'/", $js), 1);

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
