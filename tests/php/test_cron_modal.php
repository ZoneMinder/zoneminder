<?php
// Tests the form contract on the scheduled tasks page.
//
// Adding and editing a task happens in a modal built by
// web/ajax/modals/cronjob.php, and deleting happens from the list in
// web/skins/classic/views/cron.php. Neither of those files handles its own
// post: both submit to ?view=cron, and web/includes/actions/cron.php reads
// the fields back out.
//
// That makes the field names a contract between files that never mention each
// other. It used to be a contract within one file -- the form and the list
// were both in the view -- so it was hard to get wrong. Now a rename in the
// modal would leave the action reading a key nobody sends, and the failure is
// silent: the task saves with an empty schedule rather than erroring.
//
// So rather than restating the names here, where a copy would drift as
// happily as the files would, each side is parsed out of its own source and
// the two are compared.
//
// Run: php tests/php/test_cron_modal.php   (from the repo root)

$root = __DIR__.'/../..';
$modal = file_get_contents($root.'/web/ajax/modals/cronjob.php');
$view = file_get_contents($root.'/web/skins/classic/views/cron.php');
$action = file_get_contents($root.'/web/includes/actions/cron.php');
$js = file_get_contents($root.'/web/skins/classic/views/js/cron.js');

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

// The keys the action reads out of $_REQUEST['newJob'].
function keys_read($action) {
  preg_match_all("/\\\$posted\['([A-Za-z]+)'\]/", $action, $m);
  $keys = array_unique($m[1]);
  sort($keys);
  return $keys;
}

// The newJob[...] inputs a piece of source offers.
//
// Two spellings, because not every control is written as literal html: a
// <select> is built by htmlSelect(), whose first argument is the field name.
// Reading only the literal attributes would miss those and the test would
// claim a field is not submitted when it is.
function keys_offered($source) {
  preg_match_all('/name="newJob\[([A-Za-z]+)\]"/', $source, $m);
  preg_match_all("/htmlSelect\\('newJob\\[([A-Za-z]+)\\]'/", $source, $m2);
  $keys = array_unique(array_merge($m[1], $m2[1]));
  sort($keys);
  return $keys;
}

// Likewise for an id: htmlSelect() takes it in its behaviours array.
function has_id($source, $id) {
  return (bool)(preg_match('/id="'.$id.'"/', $source) or
                preg_match("/'id'\\s*=>\\s*'".$id."'/", $source));
}

echo "the modal sends every field the action reads\n";
$read = keys_read($action);
$offered = keys_offered($modal);
check('the action reads some fields', count($read) > 0, true);
check('the modal offers some fields', count($offered) > 0, true);
check('and the two sets are the same', $offered, $read);

echo "the modal is a complete, self-submitting form\n";
// A form built over ajax is outside the page's own <form>, so everything it
// needs has to be in the html that comes back.
check('it posts to the cron view', (bool)preg_match('/action="\?view=cron"/', $modal), true);
check('it posts rather than gets', (bool)preg_match('/method="post"/', $modal), true);
// index.php dispatches on view, and the modal is not rendered inside the page
// that would otherwise carry it.
check('it carries the view', (bool)preg_match('/name="view" value="cron"/', $modal), true);
// Without this the save is rejected as a forgery. getCSRFinputHTML() is put in
// by hand because nothing else wraps an ajax-built form.
check('it carries a csrf key', (bool)preg_match('/getCSRFinputHTML\(\)/', $modal), true);
check('its submit button names the save action',
      (bool)preg_match('/name="action"[^>]*value="save"/', $modal), true);
// Editing an existing task has to say which one, and adding has to say it is
// not editing: the action treats an empty id as "insert".
check('it carries the task id', (bool)preg_match('/name="id"/', $modal), true);

echo "the schedule the server stores is the one that gets submitted\n";
// Three controls, one stored value. The pickers write into the cron field,
// and it is the only one the server reads: the frequency select does post its
// own value, but under its own name, which the action ignores. A picker that
// gained a newJob[...] name would quietly become a second, competing answer
// to the same question, and which one won would depend on field order.
check('the cron field is submitted',
      (bool)preg_match('/id="scheduleSpec"[^>]*name="newJob\[Schedule\]"/s', $modal), true);
check('and it is the only newJob field describing when',
      array_values(array_filter(keys_offered($modal), function($k) {
        return in_array($k, array('Schedule', 'Frequency', 'Hour', 'Minute', 'Time'), true);
      })), array('Schedule'));
check('the time picker is not submitted at all',
      (bool)preg_match('/id="scheduleTime"[^>]*name=/s', $modal), false);
// A schedule cron can express but the pickers cannot must survive an edit, so
// the field is always present and never disabled.
check('the cron field is required', (bool)preg_match('/id="scheduleSpec"[^>]*required/s', $modal), true);

echo "the three picker ids the javascript looks for are the ones the modal uses\n";
// cron.js reads them by id after the modal html is inserted. Renaming one in
// the modal would not error; the pickers would just stop filling the field in.
preg_match_all("/getElementById\('(schedule[A-Za-z]+)'\)/", $js, $m);
$wanted = array_unique($m[1]);
sort($wanted);
$missing = array();
foreach ($wanted as $id) {
  if (!has_id($modal, $id)) $missing[] = $id;
}
check('the javascript looks for three ids', count($wanted), 3);
check('and the modal provides all of them', $missing, array());

echo "the list opens the modal and deletes\n";
// The name cell is the way in to editing, and the id has to travel with it.
check('rows carry a task id for the modal',
      (bool)preg_match('/data-jid="/', $view), true);
check('on the class the javascript binds',
      (bool)preg_match('/class="cronjobCol"/', $view), true);
check('the javascript binds that class',
      (bool)preg_match("/\\\$j\('\.cronjobCol'\)/", $js), true);
check('and asks for this modal by name',
      (bool)preg_match('/modal=cronjob/', $js), true);
// The list's own form is the delete, and it must not also claim to save.
check('the list form deletes', (bool)preg_match('/name="action" value="delete"/', $view), true);
check('and nothing in the list claims to save', (bool)preg_match('/value="save"/', $view), false);
check('marked rows post as markIds', (bool)preg_match('/name="markIds\[\]"/', $view), true);
check('the action deletes those', (bool)preg_match("/\\\$_REQUEST\['markIds'\]/", $action), true);

echo "the when row says one thing at a time\n";
// The three states of the schedule controls, checked by rendering the modal
// rather than by reading its source: which picker is selected and whether the
// cron field is open are decisions the php makes, and the source says only
// that a decision happens.
//
// Rendering needs a handful of the web environment. It is stubbed rather than
// loaded because the real thing wants a database and a readable zm.conf, and
// none of what is being checked here depends on either.
$SLANG = array();
require_once($root.'/web/lang/en_gb.php');
function translate($n) {
  global $SLANG;
  return isset($SLANG[$n]) ? $SLANG[$n] : $n;
}
function canEdit($p) {
  return true;
}
function validCardinal($i) {
  return (int)preg_replace('/\D/', '', (string)$i);
}
function validInt($i) {
  return (int)$i;
}
function validHtmlStr($i) {
  return htmlspecialchars((string)$i, ENT_QUOTES);
}
function getCSRFinputHTML() {
  return '<input type="hidden" name="__csrf_magic" value="key:stub"/>';
}
function htmlSelect($name, $contents, $values, $behaviours=false) {
  $attrs = '';
  if (is_array($behaviours)) {
    foreach ($behaviours as $event=>$action) $attrs .= ' '.$event.'="'.$action.'"';
  }
  $html = '<select name="'.$name.'"'.$attrs.'>';
  foreach ($contents as $value=>$text) {
    $html .= '<option value="'.$value.'"'.
             ((string)$value === (string)$values ? ' selected="selected"' : '').'>'.$text.'</option>';
  }
  return $html.'</select>';
}

// One row, as the modal reads it.
class StubJob {
  private $d;
  function __construct($d) {
    $this->d = $d;
  }
  function Id() {
    return $this->d['Id'];
  }
  function Name() {
    return $this->d['Name'];
  }
  function Command() {
    return $this->d['Command'];
  }
  function Schedule() {
    return $this->d['Schedule'];
  }
  function ServerId() {
    return $this->d['ServerId'];
  }
  function Enabled() {
    return $this->d['Enabled'];
  }
}
class StubServer {
  function Id() {
    return 2;
  }
  function Name() {
    return 'kitchen';
  }
}

// The modal requires its dependencies by the relative paths the web root
// provides, so they are satisfied from a throwaway include path. CronTab is
// the real one -- cronToParts() is being exercised here, not replaced.
$stub_dir = sys_get_temp_dir().'/zm_cron_modal_test_'.getmypid().'/includes';
if (!is_dir($stub_dir)) mkdir($stub_dir, 0700, true);
file_put_contents($stub_dir.'/CronJob.php',
  '<?php namespace ZM;'.
  ' class CronJob {'.
  '   static function find_one($c) { return $GLOBALS[\'STUB_JOB\']; }'.
  '   function Id() { return 0; }'.
  '   function Name() { return \'\'; }'.
  '   function Command() { return \'\'; }'.
  '   function Schedule() { return \'\'; }'.
  '   function ServerId() { return null; }'.
  '   function Enabled() { return 1; }'.
  ' }'.
  ' class Server { static function find() { return array(new \StubServer()); } }');
file_put_contents($stub_dir.'/Server.php', '<?php // in CronJob.php, above.');
file_put_contents($stub_dir.'/CronTab.php',
  '<?php require_once('.var_export(realpath($root.'/web/includes/CronTab.php'), true).');');
set_include_path(dirname($stub_dir));
register_shutdown_function(function() use ($stub_dir) {
  foreach (glob($stub_dir.'/*') as $f) unlink($f);
  @rmdir($stub_dir);
  @rmdir(dirname($stub_dir));
});

$render_warnings = array();
set_error_handler(function($n, $str) use (&$render_warnings) {
  $render_warnings[] = $str;
  return true;
});

function render_modal($id, $job) {
  $GLOBALS['STUB_JOB'] = $job;
  $_REQUEST = array('id'=>$id);
  ob_start();
  include(__DIR__.'/../../web/ajax/modals/cronjob.php');
  return ob_get_clean();
}

// Which <option> a rendered select has selected.
function selected_option($html) {
  if (!preg_match('/<select name="scheduleFrequency".*?<\/select>/s', $html, $sel)) return false;
  if (!preg_match('/<option value="([^"]*)" selected="selected"/', $sel[0], $m)) return null;
  return $m[1];
}
function time_value($html) {
  return preg_match('/id="scheduleTime"[^>]*value="([^"]*)"/', $html, $m) ? $m[1] : false;
}
function spec_value($html) {
  return preg_match('/id="scheduleSpec".*?value="([^"]*)"/s', $html, $m) ? $m[1] : false;
}
function cron_field_is_open($html) {
  return (bool)preg_match('/<details[^>]*\sopen=/', $html);
}

// Adding: nothing is set, so the pickers start somewhere sensible and the
// cron field agrees with them and stays out of the way.
$add = render_modal(0, null);
check('adding, the frequency starts at daily', selected_option($add), 'daily');
check('adding, the time starts at 03:00', time_value($add), '03:00');
check('adding, the cron field agrees with the pickers', spec_value($add), '0 3 * * *');
check('adding, the cron field is collapsed', cron_field_is_open($add), false);

// Editing a schedule the pickers can draw: they draw it.
$drawable = render_modal(7, new StubJob(array(
  'Id'=>7, 'Name'=>'porch light off',
  'Command'=>'/usr/local/bin/zmcontrol.pl --id 24 --command=lightOff',
  'Schedule'=>'30 22 * * 1', 'ServerId'=>2, 'Enabled'=>1)));
check('editing "30 22 * * 1", the frequency reads monday', selected_option($drawable), 'monday');
check('and the time reads 22:30', time_value($drawable), '22:30');
check('and the cron field holds the stored spec', spec_value($drawable), '30 22 * * 1');
check('and is collapsed, since the pickers already say it', cron_field_is_open($drawable), false);

// Editing one they cannot: this is the case the placeholder exists for. The
// pickers must not default to "Every day at 03:00" beside a cron field saying
// "*/5 * * * *" -- that is two different answers at once, and the wrong one is
// the bigger and friendlier.
$custom = render_modal(8, new StubJob(array(
  'Id'=>8, 'Name'=>'every 5 min', 'Command'=>'/usr/bin/zmaudit.pl',
  'Schedule'=>'*/5 * * * *', 'ServerId'=>null, 'Enabled'=>0)));
check('editing "*/5 * * * *", the cron field holds it unchanged',
      spec_value($custom), '*/5 * * * *');
check('and is open, so the real schedule is on screen', cron_field_is_open($custom), true);
check('and the frequency claims nothing', selected_option($custom), '');
check('and neither does the time', time_value($custom), '');
// cron.js refuses to build a spec from the placeholder, so the field survives
// until a picker is deliberately changed. The two sides agree only if the
// placeholder is a value cronSpecFromPickers() rejects.
preg_match_all("/^  ([a-z]+): '/m", $js, $m);
check('and the javascript has no rule for it', in_array('', $m[1], true), false);

// Enabled and the server round-trip too, since they are the other two things
// the modal has to read back correctly.
check('a disabled task reads as disabled',
      (bool)preg_match('/newJob\[Enabled\][^>]*checked/', $custom), false);
check('an enabled one reads as enabled',
      (bool)preg_match('/newJob\[Enabled\][^>]*checked/', $drawable), true);
check('the task\'s server is the selected one',
      (bool)preg_match('/<option value="2" selected="selected">kitchen/', $drawable), true);
check('and no server means every server',
      (bool)preg_match('/<option value="" selected="selected">Every server/', $custom), true);

echo "rendering the modal is quiet\n";
// A notice here would be served into the modal html and break the json.
restore_error_handler();
check('no warning or notice while rendering', $render_warnings, array());

echo "the add form is no longer in the page\n";
// The point of the change: the view is a list. A leftover inline form would
// still work, which is why its absence is worth pinning rather than assuming.
check('the view offers no newJob inputs', keys_offered($view), array());

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
