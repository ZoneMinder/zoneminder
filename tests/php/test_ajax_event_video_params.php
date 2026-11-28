<?php
// ajax/event.php action=video passed videoFormat, rate and scale straight to Event::createVideo(),
// which divides rate and scale and runs the format through preg_replace() into escapeshellarg().
// An array or a non-numeric rate or scale is a TypeError on PHP 8, so the request died with a 500
// instead of the 'Video Generation Failure' error the page shows. transform was read without
// checking it was sent. The form's own values, a WxH scale and a missing transform must still work.
//
// Run as: php tests/php/test_ajax_event_video_params.php [repo_root]

namespace ZM {
  class Event {
    public function __construct($row) {}
    // The operations the real Event::createVideo() does on its arguments.
    public function createVideo($f, $r, $s, $t, $o) {
      escapeshellarg(preg_replace('/[^\w]/', '', $f));
      sprintf('%.2F', ($r/100));
      if (!preg_match('/^\d+x\d+$/', $s)) sprintf('%.2F', ($s/100));
      if ($t != '') escapeshellarg(preg_replace('/[^\w=]/', '', $t));
      $GLOBALS['made'][] = array($f, $r, $s, $t);
      return '/x/Event-7.mp4';
    }
    public function canView() { return true; }
  }
}

namespace {
  class StopRequest extends Exception {}

  $stubdir = sys_get_temp_dir().'/zm_event_video_params_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  $root = $argv[1] ?? __DIR__.'/../..';

  function canView($area, $mid = false) { return true; }
  function canEdit($area, $mid = false) { return false; }
  function monitorLimitSql() { return ''; }
  function dbFetchOne($sql, $col = null, $params = array()) { return array('Id' => 7, 'MonitorId' => 1); }
  function ajaxResponse($data = array()) { throw new StopRequest('response'); }
  function ajaxError($message, $code = 200) { throw new StopRequest('error'); }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  class StubUser { public function Username() { return 'tester'; } }
  $user = new StubUser();

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run_video($root, array $params) {
    $GLOBALS['made'] = array();
    $_REQUEST = $params + array('request' => 'event', 'action' => 'video', 'id' => '7',
      'videoFormat' => 'mp4', 'rate' => '100', 'scale' => '100', 'transform' => '');
    foreach ($params as $k => $v) if ($v === null) unset($_REQUEST[$k]);
    ob_start();
    try {
      include $root.'/web/ajax/event.php';
      $outcome = 'fell through';
    } catch (StopRequest $e) {
      $outcome = $e->getMessage();
    } catch (Throwable $e) {
      $outcome = get_class($e);
    }
    ob_end_clean();
    return $outcome;
  }

  check('the form values make a video', run_video($root, array()), 'response');
  check('the form values reach createVideo', $made[0] ?? null, array('mp4', '100', '100', ''));
  check('a WxH scale makes a video', run_video($root, array('scale' => '640x480')), 'response');
  check('a missing transform makes a video', run_video($root, array('transform' => null)), 'response');
  check('a missing transform reaches createVideo as an empty string', $made[0][3] ?? null, '');
  check('a fractional rate makes a video', run_video($root, array('rate' => '12.5')), 'response');

  foreach (array(
    'an array videoFormat' => array('videoFormat' => array('mp4')),
    'an array rate' => array('rate' => array('100')),
    'a non-numeric rate' => array('rate' => 'abc'),
    'an array scale' => array('scale' => array('100')),
    'a non-numeric scale' => array('scale' => 'abc'),
    'an array transform' => array('transform' => array('hue=s=0')),
  ) as $name => $params) {
    check("$name is refused with an error", run_video($root, $params), 'error');
    check("$name makes no video", $made, array());
  }

  exit($failures ? 1 : 0);
}
