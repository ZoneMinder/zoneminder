<?php
// POST /api/events/video/<id>.json renders an event to a video file, as the
// event view's Video page does through ajax/event.php action=video. The API had
// no way to do that. The action checks the format against ZM_FFMPEG_FORMATS,
// takes only whole-number rates and scales (or WxH), refuses GET and events the
// user may not view, and answers the file name.
//
// EventsController can't be loaded without CakePHP, so video() is taken from the
// source and run against stubs.
// Run as: php tests/php/test_api_events_video.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
$start = strpos($source, 'public function video(');
if ($start === false) {
  echo "FAIL - EventsController has no video() action\n";
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class BadRequestException extends Exception {}
class InternalErrorException extends Exception {}
class MethodNotAllowedException extends Exception {}
function __($s) { return $s; }
define('ZM_OPT_FFMPEG', 1);
define('ZM_FFMPEG_FORMATS', 'mpg mpeg wmv asf avi* mov swf 3gp**');

eval('namespace ZM; class Event {
  static $viewable = true;
  static $calls = array();
  static $result = "Event-7-r1-s1.avi";
  function __construct($row) {}
  function canView() { return self::$viewable; }
  function createVideo($format, $rate, $scale, $transform, $overwrite = false) {
    self::$calls[] = array($format, $rate, $scale, $transform, $overwrite);
    return self::$result;
  }
}');

class FakeEventModel {
  public $recursive;
  function exists($id) { return $id == 7; }
  function find($type, $options) { return array('Event' => array('Id' => 7, 'MonitorId' => 1)); }
}
class FakeRequest {
  public $method = 'post';
  public $data = array();
  function allowMethod($m) {
    if ($this->method !== $m) throw new MethodNotAllowedException('only '.$m);
  }
}
eval('class FakeController { public $Event; public $request; public $out;
  function set($a) { $this->out = $a; }
  '.$method.' }');

function call_video($data, $method = 'post', $id = 7) {
  ZM\Event::$calls = array();
  $c = new FakeController();
  $c->Event = new FakeEventModel();
  $c->request = new FakeRequest();
  $c->request->method = $method;
  $c->request->data = $data;
  try {
    $c->video($id);
    return $c->out;
  } catch (Exception $e) {
    return get_class($e);
  }
}

$good = array('videoFormat' => 'avi', 'rate' => '100', 'scale' => '100');

check('a valid request answers the file name', call_video($good), array('video' => 'Event-7-r1-s1.avi', '_serialize' => array('video')));
check('the parameters reach createVideo', ZM\Event::$calls, array(array('avi', '100', '100', '', false)));
check('overwrite and transform are passed on',
  (call_video($good + array('transform' => 'hflip', 'overwrite' => '1')) and ZM\Event::$calls == array(array('avi', '100', '100', 'hflip', true))), true);
check('a WxH scale is accepted', is_array(call_video(array('scale' => '640x480') + $good)), true);
check('a format marked as default in ZM_FFMPEG_FORMATS is accepted', is_array(call_video(array('videoFormat' => '3gp') + $good)), true);
check('GET is refused', call_video($good, 'get'), 'MethodNotAllowedException');
check('an unknown event is a 404', call_video($good, 'post', 99), 'NotFoundException');
check('a format not offered is refused', call_video(array('videoFormat' => 'mkv') + $good), 'BadRequestException');
check('a format with shell characters is refused', call_video(array('videoFormat' => 'avi;id') + $good), 'BadRequestException');
check('a missing rate is refused', call_video(array('rate' => '') + $good), 'BadRequestException');
check('a fractional rate is refused', call_video(array('rate' => '1.5') + $good), 'BadRequestException');
check('a zero scale is refused', call_video(array('scale' => '0') + $good), 'BadRequestException');
check('an array scale is refused', call_video(array('scale' => array('100')) + $good), 'BadRequestException');
check('a transform with other characters is refused', call_video(array('transform' => 'a b') + $good), 'BadRequestException');
check('nothing is generated for a refused request', ZM\Event::$calls, array());
ZM\Event::$viewable = false;
check('an event the user may not view is refused', call_video($good), 'UnauthorizedException');
check('nothing is generated for it', ZM\Event::$calls, array());
ZM\Event::$viewable = true;
ZM\Event::$result = '';
check('a failed generation is a 500', call_video($good), 'InternalErrorException');

echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
exit($failures ? 1 : 0);
