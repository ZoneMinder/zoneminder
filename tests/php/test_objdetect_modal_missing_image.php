<?php
/*
 * The object detection modal used to always render an <img> pointing at
 * image.php, even when the event has no objdetect.gif or objdetect.jpg. For a
 * local event that answers 404, so the modal showed a broken image and nothing
 * else. It should say so instead.
 *
 * Run: php tests/php/test_objdetect_modal_missing_image.php [root]
 */
namespace ZM {
  class EventServer {
    public function Id() {
      return 1;
    }
  }

  class Event {
    public static $dirs = array();
    public static $hidden = array();
    private $path;

    public function __construct($path) {
      $this->path = $path;
    }

    public static function find_one(array $criteria) {
      $id = isset($criteria['Id']) ? $criteria['Id'] : null;
      if (!isset(self::$dirs[$id])) return null;
      return new Event(self::$dirs[$id]);
    }

    public function canView() {
      return !in_array($this->path, self::$hidden, true);
    }

    public function Path() {
      return $this->path;
    }

    public function Server() {
      return new EventServer();
    }
  }

  function Error($message) {}
}

namespace {
  $root = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : realpath(__DIR__.'/../..');

  function validInt($value) {
    return preg_replace('/[^\-\d]/', '', $value);
  }

  function validCardinal($value) {
    return preg_replace('/\D/', '', $value);
  }

  function translate($text) {
    return $text;
  }

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label\n";
    }
  }

  function render_modal($eid) {
    $_REQUEST = array('eid' => $eid);
    ob_start();
    include $GLOBALS['root'].'/web/ajax/modals/objdetect.php';
    return ob_get_clean();
  }

  $base = sys_get_temp_dir().'/zm_objdetect_modal_test_'.getmypid();
  $dirs = array('a' => $base.'/a', 'b' => $base.'/b', 'c' => $base.'/c');
  foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0700, true);
  }
  file_put_contents($dirs['b'].'/objdetect.jpg', 'jpg');
  file_put_contents($dirs['c'].'/objdetect.gif', 'gif');

  \ZM\Event::$dirs = array(1 => $dirs['a'], 2 => $dirs['b'], 3 => $dirs['c']);

  $empty = render_modal(1);
  check('an event with no detection image says so',
    strpos($empty, 'No object detection image') !== false);
  check('and renders no img', strpos($empty, '<img') === false);

  $jpg = render_modal(2);
  check('an event with objdetect.jpg renders an img', strpos($jpg, '<img') !== false);
  check('and it asks for fid=objdetect', strpos($jpg, 'fid=objdetect') !== false);

  $gif = render_modal(3);
  check('an event with only objdetect.gif renders an img', strpos($gif, '<img') !== false);

  // An event the user may not view answers like an unknown one.
  \ZM\Event::$hidden = array($dirs['b']);
  $hidden = render_modal(2);
  check('an event the user cannot view renders nothing', $hidden === '');
  \ZM\Event::$hidden = array();

  $missing = render_modal(999);
  check('an unknown event renders nothing', $missing === '');

  foreach (array_reverse($dirs) as $dir) {
    foreach (glob($dir.'/*') as $f) unlink($f);
    @rmdir($dir);
  }
  @rmdir($base);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
