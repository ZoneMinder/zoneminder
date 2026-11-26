<?php
// With ZM_AUTH_RELAY=none the stream URL builders put the session's username in
// user=. They read $_SESSION['username'] unguarded, so a request whose session
// has no username (token/API auth, an expired session) logged "Undefined array
// key" for every URL built. The other relay branches already tolerate a missing
// username; this checks the 'none' branch does too.
//
// Monitor::getStreamSrc(), Event::getStreamSrc() and Event::getThumbnailSrc()
// are extracted from the source and eval'd in stub classes, so no database is
// needed.
//
// Run as: php tests/php/test_stream_src_relay_none.php [web-root]
// web-root defaults to ../../web, pass another tree to test it.

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';

define('ZM_OPT_USE_AUTH', true);
define('ZM_AUTH_RELAY', 'none');
define('ZM_AUTH_HASH_IPS', false);
define('ZM_RAND_STREAM', false);
define('ZM_MIN_STREAMING_PORT', 0);

$failures = 0;
function check($name, $ok) {
  global $failures;
  if (!$ok) $failures++;
  echo ($ok ? 'ok' : 'FAIL')." - $name\n";
}

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; }, E_ALL & ~E_DEPRECATED);

function method_source($file, $name) {
  $src = file_get_contents($file);
  if (!preg_match('/^  (?:public )?function '.$name.'\(.*?^  \}/ms', $src, $m)) {
    echo "FAIL - cannot find $name in $file\n";
    exit(1);
  }
  return $m[0];
}

class ServerStub {
  public function UrlToZMS($port = null) { return 'http://h/zm/cgi-bin/nph-zms'; }
  public function Url($port = null) { return 'http://h'; }
  public function UrlToIndex($port = null) { return 'http://h/zm/index.php'; }
  public function PathToIndex() { return '/zm/index.php'; }
  public function PathToZMS() { return '/zm/cgi-bin/nph-zms'; }
}

eval('#[AllowDynamicProperties]
class MonitorStub {
  public function __construct() { $this->Id = 3; }
  public function Server() { return new ServerStub(); }
  public function ViewWidth() { return 640; }
  public function ViewHeight() { return 480; }
  '.method_source($root.'/includes/Monitor.php', 'getStreamSrc').'
}');
eval('#[AllowDynamicProperties]
class EventStub {
  public function __construct() { $this->Id = 7; $this->MonitorId = 3; $this->DefaultVideo = ""; }
  public function Server() { return new ServerStub(); }
  public function Path() { return "/nonexistent"; }
  public function ThumbnailWidth() { return 160; }
  public function ThumbnailHeight() { return 120; }
  '.method_source($root.'/includes/Event.php', 'getStreamSrc').'
  '.method_source($root.'/includes/Event.php', 'getThumbnailSrc').'
}');

$builders = array(
  'Monitor::getStreamSrc' => function() { return (new MonitorStub())->getStreamSrc(array('mode' => 'jpeg', 'scale' => 100), '&'); },
  'Event::getStreamSrc' => function() { return (new EventStub())->getStreamSrc(array('mode' => 'jpeg'), '&'); },
  'Event::getThumbnailSrc' => function() { return (new EventStub())->getThumbnailSrc(array(), '&'); },
);

foreach ($builders as $name => $build) {
  $_SESSION = array();
  $warnings = array();
  $url = $build();
  check("$name: no warning without a session username", !$warnings);
  if ($warnings) echo '  '.implode("\n  ", $warnings)."\n";
  check("$name: URL is still built", strpos($url, 'monitor=') !== false || strpos($url, 'eid=') !== false || strpos($url, 'event=') !== false);

  $_SESSION = array('username' => 'alice');
  $warnings = array();
  $url = $build();
  check("$name: user=alice with a session username", strpos($url, 'user=alice') !== false && !$warnings);
}

exit($failures ? 1 : 0);
