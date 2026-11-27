<?php
// Saving a report with no Report[...] fields in the POST raised an undefined
// index warning and passed null into Report::changes().
// Run: php tests/php/test_report_save_missing_fields.php   (from the repo root)

$stub = sys_get_temp_dir().'/zm_report_save_stub_'.getmypid();
mkdir($stub.'/includes', 0777, true);
file_put_contents($stub.'/includes/Report.php', '<?php namespace ZM; function Warning($m){} function Error($m){}
class Report { function Id(){return null;} function canEdit(){return true;} function save($c){return true;}
  function changes($v){ if (!is_array($v)) throw new \TypeError("changes() got non-array"); return array(); } }');
set_include_path($stub.PATH_SEPARATOR.get_include_path());

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

function canEdit($r) { return true; }
$action = 'save';
$_REQUEST = array();
$user = new class { function Id() { return 1; } };
$redirect = null;
$error_message = '';
try {
  include __DIR__.'/../../web/includes/actions/report.php';
} catch (\Throwable $e) {
  $warnings[] = get_class($e).': '.$e->getMessage();
}
system('rm -rf '.escapeshellarg($stub));
if ($warnings) { echo "FAIL\n".implode("\n", $warnings)."\n"; exit(1); }
echo "PASS\n";
