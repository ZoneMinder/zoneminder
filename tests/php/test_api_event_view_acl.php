<?php
// Tests EventsController::view() against the per-monitor ACL:
//   an event on a monitor the user may not view must be refused (401), not
//   answered with a stub carrying the hidden event's neighbours and frame ids.
//
// The restricted find() comes back empty for such an event. An empty ZM\Event
// has no monitor, and ZM\Event::canView() then falls back to the user's global
// Monitors permission, so canView() alone does not catch it. The stub Event
// below behaves the same way.
//
// Cake, the models and ZM\Event are stubbed, so this runs from a source
// checkout: php tests/php/test_api_event_view_acl.php

namespace ZM {
  class Event {
    public $row;
    public function __construct($row = null) { $this->row = is_array($row) ? $row : array(); }
    // Like the real one for an empty event: no monitor, so the global
    // Monitors permission decides, which the test user has.
    public function canView() {
      if (!isset($this->row['MonitorId'])) return true;
      return in_array($this->row['MonitorId'], $GLOBALS['viewable']);
    }
    public function Path() {
      return '/events/'.(isset($this->row['MonitorId']) ? $this->row['MonitorId'] : '').'/'.(isset($this->row['Id']) ? $this->row['Id'] : '');
    }
  }
  function Debug($s) {}
}

namespace {
  class App {
    public static function uses($class, $package) {}
  }
  function __($s) { return $s; }
  class NotFoundException extends Exception {}
  class UnauthorizedException extends Exception {}

  // Events 1 (monitor 1), 2 (monitor 2), 3 (monitor 1). Monitor 2 is denied.
  $GLOBALS['events'] = array(
    1 => array('Id' => 1, 'MonitorId' => 1),
    2 => array('Id' => 2, 'MonitorId' => 2),
    3 => array('Id' => 3, 'MonitorId' => 1),
  );
  $GLOBALS['viewable'] = array(1);

  class StubEventModel {
    public $recursive;
    public $primaryKey = 'Id';
    public $id;
    public function exists($id = null) { return isset($GLOBALS['events'][$id]); }
    public function unbindModel($m) {}
    public function fileExists($e) { return 0; }
    public function fileSize($e) { return 0; }
    public function find($type, $options = array()) {
      if ($type == 'first') {
        $id = null;
        $allowed = null;
        foreach ($options['conditions'] as $c) {
          if (isset($c['Event.Id'])) $id = $c['Event.Id'];
          if (isset($c['Event.MonitorId'])) $allowed = $c['Event.MonitorId'];
        }
        if (!isset($GLOBALS['events'][$id])) return array();
        $row = $GLOBALS['events'][$id];
        if ($allowed !== null and !in_array($row['MonitorId'], $allowed)) return array();
        return array('Event' => $row);
      }
      if ($type == 'neighbors') {
        return array(
          'prev' => isset($GLOBALS['events'][$this->id - 1]) ? array('Event' => array('Id' => $this->id - 1)) : null,
          'next' => isset($GLOBALS['events'][$this->id + 1]) ? array('Event' => array('Id' => $this->id + 1)) : null,
        );
      }
      return array();
    }
  }

  class StubFrameModel {
    public function findByEventid($id) { return array('Frame' => array('FrameId' => 100 + $id)); }
    public function findByEventidAndType($id, $type) { return array('Frame' => array('FrameId' => 100 + $id)); }
  }

  class StubRequest {
    public function query($k) { return null; }
  }

  class AppController {
    public $Event;
    public $Frame;
    public $request;
    public $viewVars = array();
    public function __construct() {
      $this->Event = new StubEventModel();
      $this->request = new StubRequest();
    }
    public function loadModel($m) { if ($m == 'Frame') $this->Frame = new StubFrameModel(); }
    public function set($v) { $this->viewVars = $v; }
    // As AppController: restrict to the viewable ids when some are not viewable.
    protected function viewableMonitorCondition($field) {
      return array($field => $GLOBALS['viewable']);
    }
  }

  require_once __DIR__.'/../../web/api/app/Controller/EventsController.php';

  $failures = 0;
  $passes = 0;

  function check($name, $got, $expected) {
    global $failures, $passes;
    if ($got === $expected) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).', expected '.var_export($expected, true).")\n";
    }
  }

  function view($id) {
    $c = new EventsController();
    try {
      $c->view($id);
    } catch (Exception $e) {
      return array(get_class($e), null);
    }
    return array('ok', $c->viewVars['event']['Event']);
  }

  list($r, $e) = view(1);
  check('viewable event is returned', $r, 'ok');
  check('viewable event keeps its own Id', $e['Id'], 1);
  check('viewable event has its neighbours', $e['Next'], 2);

  list($r, $e) = view(2);
  check('event on a denied monitor is refused with 401', $r, 'UnauthorizedException');
  check('no stub body for an event on a denied monitor', $e, null);

  list($r, $e) = view(99);
  check('missing event is still a 404', $r, 'NotFoundException');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
