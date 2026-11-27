<?php
// API clients could read an event's tags but not add or remove them; only the
// web UI (web/ajax/event.php addtag/removetag) could. TagsController now has
// assign() and unassign(), which take a TagId and EventIds[] and reuse the
// ajax behaviour: INSERT IGNORE (a duplicate is harmless), LastAssignedDate,
// and DELETE. Events=Edit is per area, so each event's monitor is checked too.
//
// The controller can't be loaded without CakePHP, so assign(), unassign() and
// the shared tagEventLinks() are taken from the source and run against stubs.
// requireEventsEdit() is the real AppController helper, with its require_once
// dropped because the ZM\Event stub already stands in for the real class.
// Run as: php tests/php/test_api_event_tags_assign.php [root]   (root defaults to the repo)

error_reporting(E_ALL);
$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('InternalErrorException')) { class InternalErrorException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

# Events 1 and 3 are on monitors the user may edit; event 2 is not. Event 99
# does not exist. Tag 5 exists; tag 6 does not.
eval('namespace ZM; class Event {
  static $existing = array(1, 2, 3);
  static $editable = array(1, 3);
  private $id;
  function __construct($id = null) { $this->id = intval($id); }
  function Id() { return in_array($this->id, self::$existing, true) ? $this->id : null; }
  function canEdit() { return in_array($this->id, self::$editable, true); }
}');

$GLOBALS['tags'] = array(5 => true);
$GLOBALS['links'] = array();
$GLOBALS['queries'] = array();

function dbFetchOne($sql, $col = false, $params = null) {
  if (stripos($sql, 'FROM Tags') !== false) {
    $id = intval($params[0]);
    return isset($GLOBALS['tags'][$id]) ? array($col => $id) : false;
  }
  return false;
}

function dbQuery($sql, $params = null, $debug = false) {
  $GLOBALS['queries'][] = array($sql, $params);
  if (stripos($sql, 'INSERT IGNORE INTO Events_Tags') !== false) {
    $GLOBALS['links'][intval($params[0]).':'.intval($params[1])] = true;
  } else if (stripos($sql, 'DELETE FROM Events_Tags') !== false) {
    unset($GLOBALS['links'][intval($params[0]).':'.intval($params[1])]);
  }
  return true;
}

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
}

class FakeTagModel {
  public $alias = 'Tag';
  public $primaryKey = 'Id';
}

function extract_method($file, $name) {
  $source = file_get_contents($file);
  $start = strpos($source, "function $name(");
  if ($start === false) return null;
  # Include the visibility keyword in front of the signature.
  $start = strrpos(substr($source, 0, $start), "\n") + 1;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$file = $root.'/web/api/app/Controller/TagsController.php';
$assign = is_file($file) ? extract_method($file, 'assign') : null;
$unassign = is_file($file) ? extract_method($file, 'unassign') : null;
$links = is_file($file) ? extract_method($file, 'tagEventLinks') : null;
$aclFile = $root.'/web/api/app/Controller/AppController.php';
$acl = is_file($aclFile) ? extract_method($aclFile, 'requireEventsEdit') : null;
if ($acl !== null) {
  # The stub ZM\Event above replaces includes/Event.php.
  $acl = preg_replace('/\s*require_once[^;]*;\s*/', ' ', $acl);
}

if ($assign === null or $unassign === null or $links === null or $acl === null) {
  echo "FAIL - TagsController::assign()/unassign()/tagEventLinks() or AppController::requireEventsEdit() is missing\n";
  echo "\n1 failed\n";
  exit(1);
}

eval('class TagsHarness {
  public $request, $Tag, $vars = array();
  function __construct() { $this->Tag = new FakeTagModel(); }
  function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
  function requestField($alias, $field) {
    $data = $this->request->data;
    if (!empty($data[$alias])) $data = is_array($data[$alias]) ? $data[$alias] : array();
    return isset($data[$field]) ? $data[$field] : null;
  }
  '.$acl.' '.$links.' '.$assign.' '.$unassign.' }');

function run($action, $method, $tagId, $eventIds) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new TagsHarness();
  $h->request = new FakeRequest($method, array('Tag' => array('TagId' => $tagId, 'EventIds' => $eventIds)));
  try {
    $h->$action();
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

function inserts() {
  return count(array_filter($GLOBALS['queries'], function($q) {
    return stripos($q[0], 'INSERT IGNORE INTO Events_Tags') !== false;
  }));
}

# assign adds rows for every event the user may edit.
$GLOBALS['links'] = array();
$GLOBALS['queries'] = array();
list($r, $h, $w) = run('assign', 'POST', 5, array(1, 3));
check('assign: POST succeeds', $r, 'ok');
check('assign: a row per event', array_keys($GLOBALS['links']), array('5:1', '5:3'));
check('assign: answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('assign: serializes the message', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message'));
check('assign: updates LastAssignedDate', count(array_filter($GLOBALS['queries'], function($q) {
  return stripos($q[0], 'UPDATE Tags SET LastAssignedDate = NOW()') !== false;
})), 1);
check('assign: raises no PHP warning', $w, array());

# A duplicate assign is harmless: INSERT IGNORE leaves one row and does not fail.
$GLOBALS['queries'] = array();
list($r) = run('assign', 'POST', 5, array(1));
check('duplicate assign: POST succeeds', $r, 'ok');
check('duplicate assign: still one row', array_keys($GLOBALS['links']), array('5:1', '5:3'));
check('duplicate assign: still uses INSERT IGNORE', inserts(), 1);

# An event on a monitor the user cannot edit is refused before any write.
$GLOBALS['links'] = array();
$GLOBALS['queries'] = array();
list($r) = run('assign', 'POST', 5, array(1, 2));
check('assign: an event the user cannot edit is refused', $r, 'UnauthorizedException');
check('assign: refused assign writes nothing', $GLOBALS['queries'], array());

# A missing event is a 404, like the other event actions.
list($r) = run('assign', 'POST', 5, array(99));
check('assign: a missing event is a 404', $r, 'NotFoundException');

# A missing tag is a 404.
list($r) = run('assign', 'POST', 6, array(1));
check('assign: a missing tag is a 404', $r, 'NotFoundException');

# unassign deletes the rows again.
$GLOBALS['links'] = array('5:1' => true, '5:3' => true);
list($r, $h, $w) = run('unassign', 'POST', 5, array(1, 3));
check('unassign: POST succeeds', $r, 'ok');
check('unassign: removes the rows', $GLOBALS['links'], array());
check('unassign: answers Deleted', isset($h->vars['message']) ? $h->vars['message'] : null, 'Deleted');
check('unassign: raises no PHP warning', $w, array());

# unassign checks the same per-event permission.
list($r) = run('unassign', 'POST', 5, array(2));
check('unassign: an event the user cannot edit is refused', $r, 'UnauthorizedException');

# Both actions only answer POST.
list($r) = run('assign', 'GET', 5, array(1));
check('assign: GET is a BadRequestException', $r, 'BadRequestException');
list($r) = run('unassign', 'PUT', 5, array(1));
check('unassign: PUT is a BadRequestException', $r, 'BadRequestException');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
