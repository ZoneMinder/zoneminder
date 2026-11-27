<?php
// The objdetect modal echoed the raw eid request value into the img src and
// the training link href. An eid like 1"><b>x</b> would break out of the
// attribute and inject markup into the page.
//
// The eid is now reduced to its digits before it is used, so the attribute
// cannot be broken out of, the numeric part still works, and a value with no
// digits renders nothing at all.
//
// Run: php tests/php/test_objdetect_modal_eid_escaped.php   (from the repo root)

namespace ZM {
  // The modal calls ZM\Error() when it rejects an id.
  function Error($m) {}

  // The modal looks the event up and checks it has an objdetect image; any id
  // finds an event that may be viewed and has objdetect.jpg in its path.
  class Event {
    public static $path;
    public static function find_one(array $criteria) { return new Event(); }
    public function canView() { return true; }
    public function Path() { return self::$path; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $modal = $root.'/web/ajax/modals/objdetect.php';

  $passed = 0;
  $failed = 0;
  function check($name, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $name\n";
    } else {
      $failed++;
      echo "  FAIL $name\n";
      echo "       got:  ".var_export($got, true)."\n";
      echo "       want: ".var_export($want, true)."\n";
    }
  }

  // The web environment provides the two validators. Stubbed here so the file
  // can be included without a database or a readable zm.conf.
  function validInt($i) {
    return is_scalar($i) ? preg_replace('/[^\-\d]/', '', $i) : '';
  }
  function validCardinal($i) {
    return is_scalar($i) ? preg_replace('/\D/', '', $i) : '';
  }
  function translate($s) {
    return $s;
  }
  define('ZM_OPT_TRAINING', 1);

  \ZM\Event::$path = sys_get_temp_dir().'/zm_objdetect_eid_test_'.getmypid();
  mkdir(\ZM\Event::$path, 0700, true);
  file_put_contents(\ZM\Event::$path.'/objdetect.jpg', 'jpg');

  function render($eid) {
    global $modal;
    $_REQUEST = array('eid' => $eid);
    ob_start();
    include($modal);
    return ob_get_clean();
  }

  // A well-formed id reaches both the image and the training link.
  $out = render('42');
  check('the image src carries the eid',
        strpos($out, 'eid=42&amp;fid=objdetect') !== false, true);
  check('the training link carries the eid',
        strpos($out, 'view=training&eid=42') !== false, true);

  // A value with markup in it must not break out of the attribute.
  $out = render('1"><b>x</b>');
  check('the injected markup is not echoed',
        strpos($out, '<b>x</b>') === false, true);
  check('the attribute is not broken out of',
        strpos($out, '"><b>') === false, true);
  check('the numeric part still renders an image src',
        strpos($out, 'eid=1&amp;fid=objdetect') !== false, true);

  // A value with no digits is not an event id, so nothing is rendered.
  check('a non-numeric eid renders nothing', render('abc'), '');

  unlink(\ZM\Event::$path.'/objdetect.jpg');
  rmdir(\ZM\Event::$path);

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
