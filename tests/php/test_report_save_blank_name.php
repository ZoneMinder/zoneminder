<?php
// Saving a report stored a blank Name and kept leading/trailing spaces.
// Run: php tests/php/test_report_save_blank_name.php [root]   (root defaults to the repo)
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$stub = sys_get_temp_dir().'/zm_report_name_stub_'.getmypid();
mkdir($stub.'/includes', 0777, true);
file_put_contents($stub.'/includes/Report.php', '<?php
namespace ZM { function Warning($m){} function Error($m){}
class Report { function Id(){return null;} function canEdit(){return true;}
  function save($c){ $GLOBALS["saved"] = $c; return true; }
  function changes($v){ return $v; } } }
namespace { function canEdit($area=false) { return true; } }');
set_include_path($stub.PATH_SEPARATOR.get_include_path());

function attempt($root, $name) {
  global $saved, $action, $user, $redirect, $error_message;
  $saved = null; $redirect = null; $error_message = '';
  $action = 'save';
  $_REQUEST = array('Report' => array('Name' => $name));
  $user = new class { function Id() { return 1; } };
  (function() use ($root) { global $action, $user, $redirect, $error_message; include $root.'/web/includes/actions/report.php'; })();
  return array($saved, $error_message);
}

$fail = 0;
function check($n, $ok) { global $fail; echo ($ok ? 'ok' : 'FAIL')." - $n\n"; if (!$ok) $fail++; }

list($saved, $err) = attempt($root, '   ');
check('blank name is not saved', $saved === null);
check('blank name gives an error message', $err !== '');
list($saved, $err) = attempt($root, '  Front door  ');
check('padded name is saved trimmed', is_array($saved) && ($saved['Name'] ?? null) === 'Front door');
system('rm -rf '.escapeshellarg($stub));
exit($fail ? 1 : 0);
