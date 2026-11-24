<?php
// checkJsonError() in web/includes/functions.php, run after every json_encode and
// json_decode of jsonEncode()/jsonDecode():
// - its Debug line was built from $message, which it never set: PHP 8 raised
//   "Undefined variable $message" on every malformed JSON string and the line
//   carried a backtrace with no text in front of it.
// - it ran var_export() on the value before looking at the error, so every
//   ajax response and filter query was dumped to a string for nothing.
//
// Run: php tests/php/test_check_json_error.php   (from the repo root)
//
// functions.php pulls in the whole web layer, so the two functions are lifted
// out of the file and given stub loggers.
namespace ZM {
  class Rec {
    public static $errors = array();
    public static $debug = array();
  }
  function Error($s) { Rec::$errors[] = $s; }
  function Debug($s) { Rec::$debug[] = $s; }
}

namespace {
  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if ($src === false) {
    echo "FAIL could not read functions.php\n";
    exit(1);
  }
  foreach (array('/^function checkJsonError\(.*?^}$/ms', '/^function jsonDecode\(.*?^}$/ms') as $pattern) {
    if (!preg_match($pattern, $src, $m)) {
      echo "FAIL $pattern not found in functions.php\n";
      exit(1);
    }
    eval($m[0]);
  }

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

  function run($fn) {
    ZM\Rec::$errors = array();
    ZM\Rec::$debug = array();
    $warnings = array();
    set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
    $fn();
    restore_error_handler();
    return $warnings;
  }

  $warnings = run(function() { jsonDecode('{"a":'); });
  check('malformed JSON raises no PHP warning', $warnings, array());
  check('malformed JSON is logged as an error', count(ZM\Rec::$errors), 1);
  check('the debug line starts with the same message',
    isset(ZM\Rec::$debug[0]) && strpos(ZM\Rec::$debug[0], ZM\Rec::$errors[0].' from ') === 0, true);

  $warnings = run(function() { check('valid JSON decodes', jsonDecode('{"a":1}'), array('a' => 1)); });
  check('valid JSON raises no PHP warning', $warnings, array());
  check('valid JSON logs nothing', array(ZM\Rec::$errors, ZM\Rec::$debug), array(array(), array()));

  // var_export() of a self-referencing array warns, so it shows whether the value
  // was dumped although there was no error.
  $loop = array();
  $loop['self'] = &$loop;
  json_decode('1');
  $warnings = run(function() use (&$loop) { checkJsonError($loop); });
  check('a value is not dumped when there is no JSON error', $warnings, array());

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
