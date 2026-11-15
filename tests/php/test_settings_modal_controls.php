<?php
namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  class Monitor {
    public static function find_one($a) { return new Monitor(); }
    public function Device() { return '/dev/video0'; }
    public function Name() { return 'cam'; }
    public function Brightness($v = null) {}
    public function Contrast($v = null) {}
    public function Hue($v = null) {}
    public function Colour($v = null) {}
  }
}

namespace {
  function canView($a) { return true; }
  function canEdit($a) { return true; }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validHtmlStr($v) { return htmlspecialchars($v); }
  function translate($v) { return $v; }
  function getZmuCommand($a) { return 'true'; }
  function getCSRFinputHTML() { return ''; }
  function html_radio($name, $values, $selected = null, $options = array(), $attrs = array()) {
    return '<radio '.$name.' '.implode(',', array_keys($values)).' '.$selected.' '.implode(',', array_keys($attrs)).'>';
  }

  $dir = sys_get_temp_dir().'/zm_settings_ctl_'.getmypid();
  mkdir($dir, 0700);
  file_put_contents($dir.'/v4l2-ctl', <<<'CTL'
#!/bin/sh
cat <<'X'
                     brightness 0x00980900 (int)    : min=-64 max=64 step=1 default=0 value=0
                            hue 0x00980903 (int)    : min=-40 max=40 step=1 default=0 value=0
        white_balance_automatic 0x0098090c (bool)   : default=1 value=1
                           gain 0x00980913 (int)    : min=0 max=100 step=1 default=0 value=7
           power_line_frequency 0x00980918 (menu)   : min=0 max=2 default=1 value=1 (50 Hz)
X
CTL
  );
  chmod($dir.'/v4l2-ctl', 0700);
  putenv('PATH='.$dir.PATH_SEPARATOR.getenv('PATH'));

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

  $notices = array();
  set_error_handler(function($no, $str) use (&$notices) { $notices[] = $str; return true; });
  $_REQUEST = array('mid' => '1');
  $view = '';
  ob_start();
  include __DIR__.'/../../web/ajax/modals/settings.php';
  $html = ob_get_clean();
  restore_error_handler();
  unlink($dir.'/v4l2-ctl');
  rmdir($dir);

  check('rendering raises no PHP warnings', $notices, array());
  check('a bool control is shown as radios', strpos($html, '<radio newWhite_balance_automatic 1,0 1 disabled>') !== false, true);
  check('an int control is shown as a read only slider', strpos($html, '<input type="range" min="0" max="100" step="1" value="7" disabled="disabled"/>') !== false, true);
  check('a menu control shows its value', strpos($html, '<td></td><td>1</td><td></td>') !== false, true);

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
