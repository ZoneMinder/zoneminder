<?php
/*
 * The PTZ preset modal only checked the global Monitors edit permission, so a
 * user who may edit monitors but has no Control permission on one of them
 * still got its preset labels. The control actions check canView('Control',
 * mid); the modal does the same.
 *
 * Run: php tests/php/test_controlpreset_modal_monitor_perm.php [root]
 */
namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $modalFile = $root.'/web/ajax/modals/controlpreset.php';

  // Control is allowed on monitor 5 only.
  function canEdit($a, $mid = null) { return true; }
  function canView($a, $mid = null) { return $a !== 'Control' || $mid == 5; }
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function getCSRFinputHTML() { return ''; }
  function buildSelect($name, $values) { return ''; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return array('Id' => $params[0], 'NumPresets' => 2);
  }
  function dbFetchAll($sql, $col = null, $params = array()) {
    return array(array('Preset' => 1, 'Label' => 'secret door'));
  }

  $failures = 0;
  function check($label, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  function modal($mid) {
    global $modalFile;
    $_REQUEST = array('mid' => $mid);
    ob_start();
    $result = include $modalFile;
    $html = ob_get_clean();
    return array($result, $html);
  }

  list($result, $html) = modal('5');
  check('a monitor the user may control renders the modal', strpos($html, 'ctrlPresetModal') !== false, true);

  list($result, $html) = modal('6');
  check('a monitor without Control permission returns a blank string', $result, ' ');
  check('a monitor without Control permission renders no modal', strpos($html, 'ctrlPresetModal') === false, true);
  check('a monitor without Control permission leaks no preset label', strpos($html, 'secret door') === false, true);

  exit($failures ? 1 : 0);
}

namespace ZM {
  function Warning($message) {}
}
