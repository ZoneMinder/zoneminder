<?php
/*
 * Storage and scheduled-task actions must cope with malformed requests, and the
 * Storage modal must show the stored Path rather than the Name.
 *
 * Runs the real action files against stubs, turning PHP warnings into failures.
 *
 * Run: php tests/php/test_storage_cron_actions.php
 */
$root = dirname(__DIR__, 2);
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}

eval('namespace ZM {
  function Warning($m) {} function Error($m) { $GLOBALS["errs"][] = $m; } function Info($m) {} function Debug($m) {}
  function AuditAction() {}
  class Storage {
    public $Id; public $Path = ""; public $Name = "Main";
    public function __construct($id = null) { $this->Id = $id ?: null; if ($id) $this->Name = "Main"; }
    public function Id() { return $this->Id; }
    public function Path() { return $this->Path != "" ? $this->Path : ($this->Id ? $this->Name : "/events"); }
    public function changes($new) { $c = array(); foreach ($new as $k => $v) if ($this->$k != $v) $c[$k] = $v; return $c; }
    public function save($c) { $GLOBALS["saved"][] = $c; return true; }
    public function get_last_error() { return ""; }
  }
  class CronJob {
    public $id;
    public static function find_one($p) { return $GLOBALS["jobs"][$p["Id"]] ?? false; }
    public function Id() { return $this->id; }
    public function save($v) { $GLOBALS["cron_saved"][] = $v; return true; }
    public function insert($v) { $GLOBALS["cron_inserted"][] = $v; $this->id = 99; return true; }
    public function get_last_error() { return ""; }
  }
}');
function canEdit($x) { return true; }
function validCardinal($i) { return is_scalar($i) ? preg_replace('/\D/', '', $i) : ''; }
function validInt($i) { return is_scalar($i) ? preg_replace('/[^\-\d]/', '', $i) : ''; }
function translate($k) { return $k; }
function cronScheduleIsValid($s) { return $s !== ''; }
function cronCommandIsValid($c) { return $c !== ''; }
function commandAllowedByConfig($c) { return true; }
function commandRefusedMessage() { return 'refused'; }
function cronRebuild() { return true; }
function dbQuery($sql, $args = array()) { $GLOBALS['deleted'][] = $args[0]; return true; }

// Runs an action file; returns the list of PHP warnings/exceptions it raised.
function run_action($file, $action, $request) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/'.$file);
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $GLOBALS['errs'] = $GLOBALS['saved'] = $GLOBALS['cron_saved'] = $GLOBALS['cron_inserted'] = $GLOBALS['deleted'] = array();
  $problems = array();
  set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
  $_REQUEST = $request;
  $fn = function ($action) use ($src) {
    global $error_message, $redirect;
    $error_message = ''; $redirect = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  $ex = $fn($action);
  restore_error_handler();
  if ($ex) $problems[] = $ex;
  return $problems;
}

echo "storage action\n";
$p = run_action('storage.php', 'save', array('newStorage' => array('Name' => 'x')));
check('save without id raises nothing', count($p) == 0);
check('save without id saves nothing', count($GLOBALS['saved']) == 0);
$p = run_action('storage.php', 'save', array('id' => '0'));
check('save without newStorage raises nothing', count($p) == 0);
$p = run_action('storage.php', 'save', array('id' => '0', 'newStorage' => 'junk'));
check('scalar newStorage raises nothing', count($p) == 0);
$p = run_action('storage.php', 'save', array('id' => '0', 'newStorage' => array('Name' => 'x', 'Path' => '/var/cache/zoneminder/events')));
check('valid save still saves', count($p) == 0 && count($GLOBALS['saved']) == 1);
$p = run_action('storage.php', 'save', array('id' => '0', 'newStorage' => array('Name' => 'x', 'Path' => '')));
check('save with an empty Path saves nothing', count($p) == 0 && count($GLOBALS['saved']) == 0);
$p = run_action('storage.php', 'save', array('id' => '0', 'newStorage' => array('Name' => '', 'Path' => '/var/cache/zoneminder/events')));
check('save with an empty Name saves nothing', count($p) == 0 && count($GLOBALS['saved']) == 0);

echo "cron action\n";
$p = run_action('cron.php', 'delete', array('markIds' => '5'));
check('scalar markIds raises nothing', count($p) == 0);
check('scalar markIds deletes nothing', $GLOBALS['deleted'] === array());
$p = run_action('cron.php', 'delete', array('markIds' => array('3', '4')));
check('array markIds deletes both', $GLOBALS['deleted'] === array('3', '4'));
$p = run_action('cron.php', 'save', array('newJob' => array('Schedule' => array('x'), 'Command' => 'ls')));
check('array Schedule raises nothing', count($p) == 0);
check('array Schedule is rejected', count($GLOBALS['cron_inserted']) == 0 && count($GLOBALS['cron_saved']) == 0);
$p = run_action('cron.php', 'save', array('id' => '7', 'newJob' => array('Schedule' => '* * * * *', 'Command' => 'ls')));
check('save of a deleted job does not insert', count($GLOBALS['cron_inserted']) == 0);
check('save of a deleted job reports an error', count($GLOBALS['errs']) > 0);
$job = new ZM\CronJob(); $job->id = 7; $GLOBALS['jobs'][7] = $job;
$p = run_action('cron.php', 'save', array('id' => '7', 'newJob' => array('Schedule' => '* * * * *', 'Command' => 'ls')));
check('save of an existing job updates it', count($GLOBALS['cron_saved']) == 1 && count($GLOBALS['cron_inserted']) == 0);
$p = run_action('cron.php', 'save', array('newJob' => array('Schedule' => '* * * * *', 'Command' => 'ls')));
check('save without id inserts', count($GLOBALS['cron_inserted']) == 1);

echo "storage modal\n";
$modal = file_get_contents($root.'/web/ajax/modals/storage.php');
check('modal has Path input', preg_match('/name="newStorage\[Path\]" value="<\?php echo (.*?) \?>"/', $modal, $m) === 1);
function validHtmlStr($s) { return htmlspecialchars($s); }
$expr = $m[1] ?? "''";
$existing = new ZM\Storage(3); $existing->Path = '';
$sid = 3; $newStorage = $existing;
check('existing storage with empty Path shows empty', eval("return $expr;") === '');
$existing->Path = '/mnt/a';
check('existing storage shows its Path', eval("return $expr;") === '/mnt/a');
$sid = 0; $newStorage = new ZM\Storage();
check('new storage shows default events path', eval("return $expr;") === '/events');

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
