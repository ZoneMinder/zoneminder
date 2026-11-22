<?php
/**
 * EventsController::index() accepts filters either as CakePHP named parameters
 * (/events/index/StartTime >=:...) or, when there are none, as the query string
 * (/events.json?DateTime >=...). Only the named form renamed the legacy
 * StartTime/EndTime to StartDateTime/EndDateTime and turned the DateTime
 * pseudo-attribute into an overlap test, so on the query string the first was
 * a 400 and the second reached MySQL as an unknown column (500).
 *
 * index() is run against stubs and the conditions handed to find() are checked,
 * so the test works for both the fixed and the original source.
 *
 * Usage: php tests/php/test_api_events_index_querystring_time.php [repo-root]
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : dirname(__DIR__, 2);
$controller_file = $root . '/web/api/app/Controller/EventsController.php';
$filter_file = $root . '/web/api/app/Controller/Component/FilterComponent.php';

class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class Component {}
class App { static function uses() {} }
function __($s) { return $s; }

class StubDataSource {
  function value($v, $type = null) { return "'".addslashes($v)."'"; }
}
class StubEvent {
  public $recursive;
  public $found = null;
  function schema() {
    $cols = array('Id', 'MonitorId', 'StartDateTime', 'EndDateTime', 'Length');
    return array_combine($cols, $cols);
  }
  function getDataSource() { return new StubDataSource(); }
  function find($type, $settings) { $this->found = $settings; return array(); }
}
class StubComponents {
  function load($name) { return new FilterComponent(); }
}
class StubRequest {
  public $params = array('named' => array());
  function query($k) { return null; }
}
class AppController {
  public $request;
  public $Components;
  public $Event;
  public $Paginator;
  function __construct() {
    $this->Paginator = new stdClass();
    $this->request = new StubRequest();
    $this->Components = new StubComponents();
    $this->Event = new StubEvent();
  }
  function viewableMonitorCondition($f) { return array(); }
  function set($v) {}
}

function load_php($file) {
  $src = file_get_contents($file);
  if ($src === false) {
    fwrite(STDERR, "cannot read $file\n");
    exit(2);
  }
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  return $src;
}
eval(load_php($filter_file));
$src = load_php($controller_file);
$src = str_replace("require_once __DIR__ .'/../../../includes/Event.php';", '', $src);
eval($src);

$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if ($ok) {
    echo "ok - $label\n";
  } else {
    $failures++;
    echo "FAIL - $label" . ($detail !== '' ? ": $detail" : '') . "\n";
  }
}

// Run index() with the given query string and return the find() conditions,
// or the exception message.
function run_index($query) {
  $_REQUEST = $query;
  $c = new EventsController();
  try {
    $c->index();
  } catch (Exception $e) {
    return get_class($e) . ': ' . $e->getMessage();
  }
  return $c->Event->found['conditions'][0];
}

$effective_end_marker = 'Event.EndDateTime IS NOT NULL';

// DateTime lower bound: overlap test, no bare DateTime column.
$r = run_index(array('DateTime >=' => '2024-01-01 00:00:00'));
check('query string DateTime >= is accepted', is_array($r), is_string($r) ? $r : '');
if (is_array($r)) {
  check('query string DateTime >= leaves no DateTime column',
    !array_key_exists('DateTime >=', $r) && !array_key_exists('DateTime', $r), var_export($r, true));
  $found = false;
  foreach ($r as $cond) {
    if (is_string($cond) and false !== strpos($cond, $effective_end_marker)
      and false !== strpos($cond, ">= '2024-01-01 00:00:00'")) {
      $found = true;
    }
  }
  check('query string DateTime >= tests the effective end', $found, var_export($r, true));
}

// DateTime upper bound: the event must have started by then.
$r = run_index(array('DateTime <=' => '2024-02-01 00:00:00'));
check('query string DateTime <= is accepted', is_array($r), is_string($r) ? $r : '');
if (is_array($r)) {
  check('query string DateTime <= tests StartDateTime',
    in_array(array('Event.StartDateTime <=' => '2024-02-01 00:00:00'), $r, true), var_export($r, true));
}

// Legacy names are renamed.
$r = run_index(array('StartTime >=' => '2024-01-01 00:00:00', 'EndTime <=' => '2024-02-01 00:00:00'));
check('query string StartTime/EndTime are accepted', is_array($r), is_string($r) ? $r : '');
if (is_array($r)) {
  check('query string StartTime becomes StartDateTime',
    ($r['Event.StartDateTime >='] ?? null) === '2024-01-01 00:00:00', var_export($r, true));
  check('query string EndTime becomes EndDateTime',
    ($r['Event.EndDateTime <='] ?? null) === '2024-02-01 00:00:00', var_export($r, true));
}

// Unchanged behaviour: ordinary filters and unknown fields.
$r = run_index(array('MonitorId' => '3'));
check('query string MonitorId still qualifies', is_array($r) and ($r['Event.MonitorId'] ?? null) === '3', var_export($r, true));
$r = run_index(array('Bogus' => '1'));
check('query string unknown field is still a 400', is_string($r) and 0 === strpos($r, 'BadRequestException'), var_export($r, true));

// Named form, the reference behaviour.
$c = new EventsController();
$c->request->params['named'] = array('StartTime >=' => '2024-01-01 00:00:00', 'DateTime <=' => '2024-02-01 00:00:00');
$c->index();
$named = $c->Event->found['conditions'][0];
check('named StartTime/DateTime still work',
  ($named['Event.StartDateTime >='] ?? null) === '2024-01-01 00:00:00'
  and in_array(array('Event.StartDateTime <=' => '2024-02-01 00:00:00'), $named, true), var_export($named, true));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
