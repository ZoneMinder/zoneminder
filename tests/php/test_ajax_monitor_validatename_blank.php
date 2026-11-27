<?php
// ajax/monitor.php action=validateName:
//  - a whitespace-only monitorName ("   ") used to pass the non-empty check,
//    so validateName answered response=true while the save path trims the name
//    to '' and rejects it. It must be treated as missing and go to the
//    ajaxError(translate('ErrorVerifyingMonitorName')) branch.
//  - a truly empty monitorName is already treated as missing.
//  - a normal name is still accepted, and a name with bad characters still
//    comes back as response=false.
//
// The ajax file does require_once('includes/Monitor.php'), so each include
// runs from a temp dir holding a stub includes/Monitor.php. The file is
// included once per scenario in this process, so run with no arguments.
//
// Run as: php tests/php/test_ajax_monitor_validatename_blank.php [path/to/ajax/monitor.php]

namespace {
$zmMonitorFile = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/ajax/monitor.php';
$zmMonitorFile = realpath($zmMonitorFile);
if ( $zmMonitorFile === false || !is_file($zmMonitorFile) ) {
  fwrite(STDERR, "cannot find ajax/monitor.php\n");
  exit(2);
}

$stubdir = sys_get_temp_dir().'/zm_monitor_validatename_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Monitor.php', <<<'STUB'
<?php
namespace ZM;
class Monitor {
  private $id;
  public function __construct($id = null) { $this->id = (int)$id; }
  public function Id() { return $this->id; }
  public function getDefaults() {
    return array('Name' => array('filter_regexp' => '/[^\w\p{L}\p{M}\p{N}\-\.\(\)\:\/ ]/u'));
  }
}
STUB
);
register_shutdown_function(function() use ($stubdir) {
  @unlink($stubdir.'/includes/Monitor.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir);
});

// The ajax file resolves includes/Monitor.php relative to the working directory.
if ( !chdir($stubdir) ) {
  fwrite(STDERR, "cannot chdir to stub dir\n");
  exit(2);
}

class AjaxDone extends Exception {
  public $kind;
  public $payload;
  public function __construct($kind, $payload) {
    parent::__construct($kind);
    $this->kind = $kind;
    $this->payload = $payload;
  }
}

function canView($area, $mid = null) { return true; }
function translate($s) { return $s; }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }
function validCardinal($v) { return ctype_digit((string)$v) ? (int)$v : 0; }
function ajaxError($m) { throw new AjaxDone('error', $m); }
function ajaxResponse($d) { throw new AjaxDone('response', $d); }

class TestUser {
  public function Username() { return 'tester'; }
}
$user = new TestUser();

$scenarios = array(
  array('label' => 'whitespace-only name is missing', 'monitorName' => '   ', 'kind' => 'error', 'response' => null),
  array('label' => 'empty name is missing', 'monitorName' => '', 'kind' => 'error', 'response' => null),
  array('label' => 'normal name is accepted', 'monitorName' => 'Front Door', 'kind' => 'response', 'response' => true),
  array('label' => 'name with bad characters is rejected', 'monitorName' => 'bad<name>', 'kind' => 'response', 'response' => false),
);

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ( $got === $want ) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

foreach ( $scenarios as $scenario ) {
  echo "== ".$scenario['label']." ==\n";

  $_REQUEST = array('mid' => '5', 'action' => 'validateName', 'monitorName' => $scenario['monitorName']);

  $caught = null;
  try {
    include $zmMonitorFile;
  } catch ( AjaxDone $e ) {
    $caught = $e;
  } catch ( Throwable $t ) {
    $caught = $t;
  }

  check('handler answered', $caught instanceof AjaxDone, true);
  if ( !$caught instanceof AjaxDone ) {
    if ( $caught ) echo "  unexpected ".get_class($caught).': '.$caught->getMessage()."\n";
    continue;
  }

  check('answer kind', $caught->kind, $scenario['kind']);
  if ( $scenario['kind'] === 'error' ) {
    check('error message', $caught->payload, 'ErrorVerifyingMonitorName');
  } else {
    check('response value', $caught->payload['response'] ?? null, $scenario['response']);
  }
}

echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
exit($failures ? 1 : 0);
}
