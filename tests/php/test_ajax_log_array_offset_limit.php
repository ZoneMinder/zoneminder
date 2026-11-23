<?php
// The Log table is paginated from the offset and limit request parameters. A
// request such as offset[]=x used to be cast straight to int, which logged an
// "Array to string conversion" notice and an error whose message read "Array".
// The validation now checks is_scalar first, so an array falls back to the
// default without a notice and the error names the type instead.
//
// php tests/php/test_ajax_log_array_offset_limit.php [root]   (root defaults to the repo)

namespace ZM {
  function Error($m) { $GLOBALS['errors'][] = $m; }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $src = file_get_contents($root.'/web/ajax/log.php');
  if (!preg_match('/\n(\s*\$offset = 0;.*?)\n\s*\/\/ The table we want our data from/s', $src, $m)) {
    echo "FAIL - offset/limit validation block not found\n\n1 failed\n";
    exit(1);
  }
  $block = $m[1];

  $GLOBALS['php_errors'] = array();
  set_error_handler(function($errno, $errstr) {
    $GLOBALS['php_errors'][] = $errstr;
    return true;
  }, E_ALL);

  // Each case runs the extracted statements in its own scope with $_REQUEST set.
  function run_case($block, $request) {
    $GLOBALS['errors'] = array();
    $GLOBALS['php_errors'] = array();
    $_REQUEST = $request;
    eval($block);
    return array($offset, $limit, $GLOBALS['errors'], $GLOBALS['php_errors']);
  }

  $failures = 0;
  function check($label, $ok) {
    global $failures;
    if (!$ok) $failures++;
    echo ($ok ? 'ok - ' : 'FAIL - ').$label."\n";
  }

  list($offset, $limit, $errors, $php_errors) = run_case($block, array(
    'offset' => array('x'),
    'limit' => array('y'),
  ));
  check('array offset/limit records no PHP notice or warning', count($php_errors) === 0);
  check('array offset falls back to 0', $offset == 0);
  check('array limit falls back to 100', $limit == 100);
  check('array offset/limit logs exactly two errors', count($errors) === 2);
  $has_array = false;
  foreach ($errors as $e) {
    if (strpos($e, 'Array') !== false) $has_array = true;
  }
  check('array error messages do not contain "Array"', !$has_array);

  list($offset, $limit, $errors, $php_errors) = run_case($block, array(
    'offset' => '5',
    'limit' => '20',
  ));
  check('numeric strings are accepted as offset and limit', $offset === 5 and $limit === 20);
  check('valid offset/limit logs no errors', count($errors) === 0);

  list($offset, $limit, $errors, $php_errors) = run_case($block, array(
    'offset' => 'abc',
    'limit' => '-1',
  ));
  check('non-numeric offset falls back to 0', $offset == 0);
  check('negative limit falls back to 100', $limit == 100);
  check('invalid offset/limit logs exactly two errors', count($errors) === 2);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
