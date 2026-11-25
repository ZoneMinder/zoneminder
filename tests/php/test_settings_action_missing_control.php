<?php
// The settings modal only has a slider for each control v4l2-ctl lists, so a camera without
// a hue (or contrast, ...) control posts no value for it, and the settings action read the
// missing request key anyway: a PHP "Undefined array key" warning on every save. The bare
// option it passes zmu for that control (read, don't set) is unchanged.
//
// Includes the real web/includes/actions/settings.php with the web helpers stubbed.
//
// Run as: php tests/php/test_settings_action_missing_control.php [settings.php]

namespace ZM {
  function Warning($s) {}
}

namespace {
  $stubdir = sys_get_temp_dir().'/zm_settings_missing_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Monitor.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canView($area, $mid = false) { return true; }
  function canEdit($area, $mid = false) { return true; }
  function validInt($v) { return is_scalar($v) ? preg_replace('/[^\-\d]/', '', $v) : ''; }
  function getZmuCommand($args) {
    $GLOBALS['zmu_args'] = $args;
    return 'printf %s '.escapeshellarg('11 22 33 44');
  }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = $params; return true; }

  $file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/settings.php';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run_action($request) {
    global $file;
    $GLOBALS['zmu_args'] = null;
    $GLOBALS['queries'] = array();
    $GLOBALS['php_warnings'] = array();
    set_error_handler(function ($no, $str) { $GLOBALS['php_warnings'][] = $str; return true; });
    $_REQUEST = $request;
    $action = 'settings';
    include $file;
    restore_error_handler();
  }

  $all = array('mid' => '1', 'newBrightness' => '11', 'newContrast' => '22', 'newHue' => '33', 'newColour' => '44');

  run_action($all);
  check('all four sliders posted: no PHP warning', $php_warnings, array());
  check('all four sliders posted: values passed to zmu',
    $zmu_args, " -m '1' -B'11' -C'22' -H'33' -O'44'");

  $noHue = $all;
  unset($noHue['newHue']);
  run_action($noHue);
  check('no hue slider: no PHP warning', $php_warnings, array());
  check('no hue slider: bare -H reads the current hue', $zmu_args, " -m '1' -B'11' -C'22' -H'' -O'44'");
  check('no hue slider: what zmu reports is still stored', count($queries), 1);

  $onlyBrightness = array('mid' => '1', 'newBrightness' => '11');
  run_action($onlyBrightness);
  check('only brightness posted: no PHP warning', $php_warnings, array());

  @unlink($stubdir.'/includes/Monitor.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
