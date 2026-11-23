<?php
/*
 * The confirm modals translate a key taken from the request. An unknown key is
 * returned as is, so the key must not reach the page as markup.
 *
 * Run: php tests/php/test_modal_key_escape.php [root]
 */
$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

function translate($s) { return $s; }

$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  FAIL - $label\n";
  }
}

foreach (array('saveconfirm', 'eventunarchive') as $modal) {
  $_REQUEST = array('key' => '<img src=x onerror=alert(1)>');
  ob_start();
  include $root.'/web/ajax/modals/'.$modal.'.php';
  $html = ob_get_clean();
  check("$modal: key is not emitted as markup", strpos($html, '<img src=x') === false);

  $_REQUEST = array('key' => 'a&b');
  ob_start();
  include $root.'/web/ajax/modals/'.$modal.'.php';
  $html = ob_get_clean();
  check("$modal: a plain key is still shown", strpos($html, 'a&amp;b') !== false);

  $_REQUEST = array('key' => array('x'));
  ob_start();
  include $root.'/web/ajax/modals/'.$modal.'.php';
  $html = ob_get_clean();
  check("$modal: an array key falls back to the default", strpos($html, 'Confirm') !== false);
}

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
