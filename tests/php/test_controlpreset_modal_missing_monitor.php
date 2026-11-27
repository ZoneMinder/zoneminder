<?php
/*
 * Opening the PTZ preset modal for a monitor that was deleted, or that has no
 * control (the INNER JOIN on Controls finds no row), used to read Id/NumPresets
 * from the false returned by dbFetchOne(). On PHP 8 that raised repeated
 * "Trying to access array offset on value of type bool" warnings and rendered an
 * empty modal. A missing mid also raised "Undefined array key". The modal now
 * validates mid and returns ' ' with a ZM\Warning when there is no monitor.
 *
 * Run: php tests/php/test_controlpreset_modal_missing_monitor.php [root]
 * Pass a worktree root as the first argument to test another copy of the file
 * (used to show the failure against the pre-fix version).
 */
namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $modalFile = $root.'/web/ajax/modals/controlpreset.php';

  function canEdit($a) { return true; }
  function canView($a, $mid = null) { return true; }
  // Same behaviour as web/includes/functions.php.
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function getCSRFinputHTML() { return ''; }
  function buildSelect($name, $values) { return ''; }
  function dbFetchOne($sql, $col = null, $params = array()) { return false; }
  function dbFetchAll($sql, $col = null, $params = array()) { return array(); }

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

  $phpErrors = array();
  set_error_handler(function($no, $str) use (&$phpErrors) {
    $phpErrors[] = $str;
    return true;
  });

  // (a) A deleted monitor, or one whose ControlId has no Controls row.
  $phpErrors = array();
  $GLOBALS['zmWarnings'] = array();
  $_REQUEST = array('mid' => '999');
  ob_start();
  $result = include $modalFile;
  $html = ob_get_clean();
  check('missing monitor row raises no PHP warning', count($phpErrors) === 0);
  if (count($phpErrors)) {
    foreach ($phpErrors as $err) echo "         php: $err\n";
  }
  check('missing monitor row returns blank string', $result === ' ');
  check('missing monitor row logs a ZM\Warning', count($GLOBALS['zmWarnings']) > 0);
  check('missing monitor row renders no modal', strpos($html, 'ctrlPresetModal') === false);

  // (b) No mid in the request at all.
  $phpErrors = array();
  $GLOBALS['zmWarnings'] = array();
  $_REQUEST = array();
  ob_start();
  $result = include $modalFile;
  $html = ob_get_clean();
  check('absent mid raises no PHP warning', count($phpErrors) === 0);
  if (count($phpErrors)) {
    foreach ($phpErrors as $err) echo "         php: $err\n";
  }
  check('absent mid returns blank string', $result === ' ');
  check('absent mid logs a ZM\Warning', count($GLOBALS['zmWarnings']) > 0);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}

namespace ZM {
  function Warning($message) {
    $GLOBALS['zmWarnings'][] = $message;
  }
}
