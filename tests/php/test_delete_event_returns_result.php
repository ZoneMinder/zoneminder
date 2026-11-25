<?php
/*
 * The event and events delete actions check the result of deleteEvent().
 * deleteEvent() returns (bool)$event->delete(), and Event::delete() returns
 * true once the event is deleted and false when it refuses or the DELETE
 * fails. This runs the real deleteEvent() with a fake event, and checks
 * that Event::delete() ends by returning true.
 *
 * Run: php tests/php/test_delete_event_returns_result.php [repo-root]
 */
namespace ZM {
  function Error($m) {}
  function Info($m) {}
  function Warning($m) {}
  class Event {
    public $id; public $archived = false; public $editable = true; public $deleteOk = true;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function Archived() { return $this->archived; }
    function canEdit() { return $this->editable; }
    function delete() { return $this->deleteOk; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) { echo "  ok   - $label\n"; } else { $failures++; echo "  FAIL - $label\n"; }
  }
  function extract_body($src, $sig) {
    $at = strpos($src, $sig);
    if ($at === false) return null;
    $brace = strpos($src, '{', $at);
    $depth = 0;
    for ($i = $brace; $i < strlen($src); $i++) {
      if ($src[$i] === '{') $depth++;
      elseif ($src[$i] === '}') { $depth--; if ($depth === 0) return substr($src, $at, $i - $at + 1); }
    }
    return null;
  }

  $fn = extract_body(file_get_contents($root.'/web/includes/functions.php'), 'function deleteEvent(');
  check('functions.php defines deleteEvent()', $fn !== null);
  eval($fn);

  $e = new ZM\Event(5);
  check('a deleted event returns true', deleteEvent($e) === true);
  $e->deleteOk = false;
  check('a failed Event::delete() returns false', deleteEvent($e) === false);
  $e = new ZM\Event(6); $e->archived = true;
  check('an archived event returns false', deleteEvent($e) === false);
  $e = new ZM\Event(7); $e->editable = false;
  check('an event the user cannot edit returns false', deleteEvent($e) === false);

  $del = extract_body(file_get_contents($root.'/web/includes/Event.php'), 'public function delete(');
  check('Event::delete() returns true at the end', $del !== null && preg_match('/return true;\s*\}\s*$/', $del));
  check('Event::delete() returns false when the DELETE fails', $del !== null && strpos($del, 'return false;') !== false);

  echo "$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
