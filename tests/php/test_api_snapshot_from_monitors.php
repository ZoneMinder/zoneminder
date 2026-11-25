<?php
// POST /api/snapshots used to accept only EventIds, so an API client could not do
// what the web UI does in includes/actions/snapshot.php: force an alarm on each
// monitor, link the events it records and archive them. MonitorIds now takes that
// same path, which lives in includes/SnapshotCreate.php and is shared with the action.
//
// The controller can't be loaded without CakePHP, so add() is taken from the source
// and run against stubs. The shared helper is loaded against the same stubs.
//
// Run as: php tests/php/test_api_snapshot_from_monitors.php [root]

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $GLOBALS['root'] = $root;
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

  // The helper requires these files; keep them empty so the stubs below are used.
  $stubdir = sys_get_temp_dir().'/zm_snapshot_api_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Snapshot.php', "<?php\n");
  file_put_contents($stubdir.'/includes/Monitor.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  $GLOBALS['stubdir'] = $stubdir;

  $helper = $root.'/web/includes/SnapshotCreate.php';
  if (!is_file($helper)) {
    echo "FAIL - $helper not found\n";
    exit(1);
  }
}

namespace ZM {
  function Warning($s) { $GLOBALS['log'][] = 'warning: '.$s; }
  function Error($s) { $GLOBALS['log'][] = 'error: '.$s; }
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) { $GLOBALS['audit'][] = "$a $b $c"; }

  class Snapshot {
    private $id;
    public function __construct($id = null) { $this->id = $id; }
    public function save($data = null) {
      $GLOBALS['snapshot_saves'][] = $data;
      if (!$this->id) $this->id = ++$GLOBALS['snapshot_seq'];
      return true;
    }
    public function Id() { return $this->id; }
    public function EventIds() {
      return isset($GLOBALS['links'][$this->id]) ? array_values($GLOBALS['links'][$this->id]) : array();
    }
    public function Events() {
      $events = array();
      foreach ($this->EventIds() as $event_id) $events[] = new Event($event_id);
      return $events;
    }
    public function delete() {
      $GLOBALS['deleted_snapshots'][] = $this->id;
      unset($GLOBALS['links'][$this->id]);
      return true;
    }
  }

  class Snapshot_Event {
    public function save($data) {
      $GLOBALS['links'][$data['SnapshotId']][] = (int)$data['EventId'];
      return true;
    }
  }

  class Monitor {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public static function find_one($where) {
      $id = (int)$where['Id'];
      return in_array($id, $GLOBALS['monitors'], true) ? new Monitor($id) : null;
    }
    public function Id() { return $this->id; }
    public function canEdit() {
      return in_array($this->id, $GLOBALS['visible'], true)
        && in_array($this->id, $GLOBALS['editable'], true);
    }
    public function TriggerOn() {
      $GLOBALS['done'][] = 'TriggerOn '.$this->id;
      return isset($GLOBALS['event_for'][$this->id]) ? $GLOBALS['event_for'][$this->id] : 0;
    }
    public function TriggerOff() { $GLOBALS['done'][] = 'TriggerOff '.$this->id; }
  }

  class Event {
    private $id;
    public function __construct($id = null) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function lock() { $GLOBALS['done'][] = 'lock '.$this->id; }
    public function save($data) { $GLOBALS['done'][] = 'archive '.$this->id; return true; }
  }
}

namespace {
  require_once $GLOBALS['root'].'/web/includes/SnapshotCreate.php';

  if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
  if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
  if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
  if (!function_exists('__')) { function __($s) { return $s; } }

  function canEdit($area, $mid = false) {
    if ($area === 'Snapshots') return !empty($GLOBALS['snap_edit']);
    return true;
  }
  function visibleMonitor($mid) { return in_array($mid, $GLOBALS['visible'], true); }

  class FakeUser {
    private $snapshots;
    public function __construct($snapshots = 'Edit') { $this->snapshots = $snapshots; }
    public function Id() { return 2; }
    public function Snapshots() { return $this->snapshots; }
  }

  class StubConn {
    public $begins = 0;
    public $commits = 0;
    public function beginTransaction() { $this->begins++; }
    public function commit() { $this->commits++; }
  }

  class FakeRequest {
    public $data;
    private $method;
    public function __construct($method, $data) { $this->method = $method; $this->data = $data; }
    public function is($type) {
      return in_array(strtolower($this->method), array_map('strtolower', (array)$type));
    }
  }

  class FakeSnapshotModel {
    public $id = null;
    public $validationErrors = array();
    public function create() { $this->id = false; }
    public function saveAssociated($data = null, $options = array()) {
      $GLOBALS['save_associated_data'] = $data;
      $this->id = 77;
      return true;
    }
  }

  $source = file_get_contents($GLOBALS['root'].'/web/api/app/Controller/SnapshotsController.php');
  $start = strpos($source, 'public function add(');
  if ($start === false) {
    echo "FAIL - SnapshotsController::add() not found\n";
    exit(1);
  }
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  $method = substr($source, $start, $i - $start + 1);

  eval('class SnapshotControllerHarness {
    public $request;
    public $Snapshot;
    public $vars = array();
    public function set($v, $value = null) {
      $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value));
    }
    public function requestField($alias, $field) {
      $data = $this->request->data;
      if (!empty($data[$alias])) {
        $data = is_array($data[$alias]) ? $data[$alias] : array();
      }
      return isset($data[$field]) ? $data[$field] : null;
    }
    public function requestAssociatedIds($alias, $assoc) {
      if (!isset($this->request->data[$assoc])) return null;
      $rows = $this->request->data[$assoc];
      if (!is_array($rows)) { unset($this->request->data[$assoc]); return null; }
      if (isset($rows[$assoc])) $rows = $rows[$assoc];
      $ids = array();
      foreach ((array)$rows as $row) {
        if (is_array($row)) $row = isset($row["EventId"]) ? $row["EventId"] : null;
        if (is_numeric($row)) $ids[] = intval($row);
      }
      $this->request->data[$assoc] = array($assoc => $ids);
      return $ids;
    }
    public function requireEventsView($ids) { $GLOBALS["view_checked"] = $ids; }
    public function pinRequestId($model, $id) { $model->id = $id; }
    '.$method.' }');

  function reset_state($user, $visible, $editable, $monitors, $event_for) {
    $GLOBALS['user'] = $user;
    $GLOBALS['snap_edit'] = true;
    $GLOBALS['visible'] = $visible;
    $GLOBALS['editable'] = $editable;
    $GLOBALS['monitors'] = $monitors;
    $GLOBALS['event_for'] = $event_for;
    $GLOBALS['snapshot_seq'] = 0;
    $GLOBALS['snapshot_saves'] = array();
    $GLOBALS['deleted_snapshots'] = array();
    $GLOBALS['links'] = array();
    $GLOBALS['done'] = array();
    $GLOBALS['log'] = array();
    $GLOBALS['audit'] = array();
    $GLOBALS['dbConn'] = new StubConn();
    $GLOBALS['view_checked'] = null;
    $GLOBALS['save_associated_data'] = null;
  }

  function run_add($data) {
    $h = new SnapshotControllerHarness();
    $h->request = new FakeRequest('POST', $data);
    $h->Snapshot = new FakeSnapshotModel();
    try {
      $h->add();
    } catch (Throwable $e) {
      return array(get_class($e), $h);
    }
    return array('ok', $h);
  }

  function has($needle) { return in_array($needle, $GLOBALS['done'], true); }

  // (1) MonitorIds creates a snapshot the way the web action does: a snapshot row,
  // an alarm on each monitor, links to the recorded events, then archive.
  reset_state(new FakeUser(), array(1, 2), array(1, 2), array(1, 2), array(1 => 101, 2 => 102));
  list($result, $h) = run_add(array('Snapshot' => array('MonitorIds' => array('1', '2'))));
  check('MonitorIds: request succeeds', $result, 'ok');
  check('MonitorIds: answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check('MonitorIds: answers the new snapshot id', isset($h->vars['id']) ? $h->vars['id'] : null, 1);
  check('MonitorIds: snapshot records the user', isset($GLOBALS['snapshot_saves'][0]['CreatedBy']) ? $GLOBALS['snapshot_saves'][0]['CreatedBy'] : null, 2);
  check('MonitorIds: audits the snapshot', isset($GLOBALS['audit'][0]) ? $GLOBALS['audit'][0] : null, 'create snapshot 1');
  check('MonitorIds: triggers monitor 1', has('TriggerOn 1'), true);
  check('MonitorIds: triggers monitor 2', has('TriggerOn 2'), true);
  check('MonitorIds: releases monitor 1', has('TriggerOff 1'), true);
  check('MonitorIds: releases monitor 2', has('TriggerOff 2'), true);
  check('MonitorIds: links the recorded events', isset($GLOBALS['links'][1]) ? $GLOBALS['links'][1] : null, array(101, 102));
  check('MonitorIds: archives the recorded events', array_values(array_filter($GLOBALS['done'], function($d) {
    return strpos($d, 'archive ') === 0;
  })), array('archive 101', 'archive 102'));
  check('MonitorIds: archives in a transaction', array($GLOBALS['dbConn']->begins, $GLOBALS['dbConn']->commits), array(1, 1));

  // (2) A monitor the user may not view is not triggered, and nothing is created
  // when no monitor is left.
  reset_state(new FakeUser(), array(1), array(1), array(1, 2), array(1 => 101, 2 => 102));
  list($result, $h) = run_add(array('Snapshot' => array('MonitorIds' => array('2'))));
  check('unviewable monitor: no snapshot row', $GLOBALS['snapshot_saves'], array());
  check('unviewable monitor: not triggered', $GLOBALS['done'], array());
  check('unviewable monitor: no id', isset($h->vars['id']) ? $h->vars['id'] : null, null);
  check('unviewable monitor: answers Error', isset($h->vars['message']) ? $h->vars['message'] : null, 'Error');

  // A monitor the user may view but not edit is skipped, the editable one is used.
  reset_state(new FakeUser(), array(1, 2), array(1), array(1, 2), array(1 => 101, 2 => 102));
  list($result, $h) = run_add(array('Snapshot' => array('MonitorIds' => array('2', '1'))));
  check('view-only monitor: not triggered', has('TriggerOn 2'), false);
  check('view-only monitor: editable monitor triggered', has('TriggerOn 1'), true);
  check('view-only monitor: only the editable event is linked', isset($GLOBALS['links'][1]) ? $GLOBALS['links'][1] : null, array(101));

  // (3) The existing EventIds form still links events through the model and returns
  // its id; it must not trigger monitors.
  reset_state(new FakeUser(), array(), array(), array(), array());
  list($result, $h) = run_add(array('Snapshot' => array('EventIds' => '1')));
  check('EventIds: request succeeds', $result, 'ok');
  check('EventIds: answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check('EventIds: answers the model id', isset($h->vars['id']) ? $h->vars['id'] : null, 77);
  check('EventIds: links the event', isset($GLOBALS['save_associated_data']['Event']) ? $GLOBALS['save_associated_data']['Event'] : null, array('Event' => array(1)));
  check('EventIds: checks the event can be viewed', $GLOBALS['view_checked'], array(1));
  check('EventIds: triggers no monitor', $GLOBALS['done'], array());

  // The bare Event list form still works too.
  reset_state(new FakeUser(), array(), array(), array(), array());
  list($result, $h) = run_add(array('Event' => array('2')));
  check('Event list: request succeeds', $result, 'ok');
  check('Event list: answers the model id', isset($h->vars['id']) ? $h->vars['id'] : null, 77);
  check('Event list: links the event', isset($GLOBALS['save_associated_data']['Event']) ? $GLOBALS['save_associated_data']['Event'] : null, array('Event' => array(2)));

  @unlink($GLOBALS['stubdir'].'/includes/Snapshot.php');
  @unlink($GLOBALS['stubdir'].'/includes/Monitor.php');
  @rmdir($GLOBALS['stubdir'].'/includes');
  @rmdir($GLOBALS['stubdir']);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
