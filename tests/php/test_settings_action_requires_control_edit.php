<?php
// Security regression test for includes/actions/settings.php (the brightness,
// contrast, hue and colour of a local camera):
//   * it needs Control edit, as the settings modal shows; it used to run for
//     Control view
//   * it stores only the four values zmu reports. zmu prints nothing when it
//     refuses (it checks the user's own permissions) and the action used to
//     write that empty output into Monitors anyway.
//
// Includes the real file with the web helpers stubbed; zmu is replaced by a
// command that prints what the test chooses, and the SQL sent is recorded.
//
// Run as: php tests/php/test_settings_action_requires_control_edit.php

namespace ZM {
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
}

namespace {
  $stubdir = sys_get_temp_dir().'/zm_settings_action_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Monitor.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canView($area, $mid = false) {
    if ($mid and !$GLOBALS['monitor_visible']) return false;
    return in_array($GLOBALS['perms'][$area] ?? 'None', array('View', 'Edit'), true);
  }
  function canEdit($area, $mid = false) {
    if ($mid and !$GLOBALS['monitor_visible']) return false;
    return ($GLOBALS['perms'][$area] ?? 'None') === 'Edit';
  }
  function validInt($v) { return (int)$v; }
  function getZmuCommand($args) {
    $GLOBALS['zmu_args'] = $args;
    return 'printf %s '.escapeshellarg($GLOBALS['zmu_output']);
  }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array(trim($sql), $params); return true; }

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  function run_action($control, $zmuOutput, $visible = true) {
    $GLOBALS['perms'] = array('Control' => $control, 'Monitors' => 'View');
    $GLOBALS['monitor_visible'] = $visible;
    $GLOBALS['zmu_output'] = $zmuOutput;
    $GLOBALS['zmu_args'] = null;
    $GLOBALS['queries'] = array();
    $GLOBALS['warnings'] = array();
    $_REQUEST = array('mid' => '1', 'newBrightness' => '11', 'newContrast' => '22',
                      'newHue' => '33', 'newColour' => '44');
    $action = 'settings';
    include __DIR__.'/../../web/includes/actions/settings.php';
  }

  run_action('View', '11 22 33 44');
  check('Control=View does not run zmu', $GLOBALS['zmu_args'], null);
  check('Control=View writes nothing', $GLOBALS['queries'], array());
  check('Control=View logs a warning', count($GLOBALS['warnings']), 1);

  run_action('Edit', '11 22 33 44', false);
  check('Control=Edit on a hidden monitor does not run zmu', $GLOBALS['zmu_args'], null);
  check('Control=Edit on a hidden monitor writes nothing', $GLOBALS['queries'], array());

  run_action('Edit', '11 22 33 44');
  check('Control=Edit runs zmu', is_string($GLOBALS['zmu_args']), true);
  check('Control=Edit stores what zmu reported', $GLOBALS['queries'], array(array(
    'UPDATE Monitors SET Brightness = ?, Contrast = ?, Hue = ?, Colour = ? WHERE Id = ?',
    array('11', '22', '33', '44', 1))));

  run_action('Edit', '');
  check('Control=Edit, zmu refused (no output): writes nothing', $GLOBALS['queries'], array());
  check('Control=Edit, zmu refused (no output): logs a warning', count($GLOBALS['warnings']), 1);

  run_action('Edit', 'garbage');
  check('Control=Edit, zmu output not four numbers: writes nothing', $GLOBALS['queries'], array());

  run_action('Edit', '-1 -1 -1 -1');
  check('Control=Edit, zmu reports -1 (no V4L2 control): stored', count($GLOBALS['queries']), 1);

  @unlink($stubdir.'/includes/Monitor.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
