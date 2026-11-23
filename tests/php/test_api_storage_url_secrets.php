<?php
// The Url of an s3fs storage area is s3://ACCESSKEY:SECRETKEY@host/bucket. That
// Url is returned by GET /storage, GET /storage/view/ID and (as the Storage row
// Cake joins onto an event) GET /events/view/ID, so every one of them must hide
// the key and secret from users who cannot edit System. A user with System Edit,
// and an unauthenticated request (auth off, $user null), keep the Url.
//
// AppController::hideStorageSecrets(), StorageController::index()/view() and
// EventsController::view() are extracted from the sources and run against fake
// Cake models, so this works from a checkout:
//
//   php tests/php/test_api_storage_url_secrets.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

// hideStorageSecrets() calls ZM\removeUserinfo(); it is defined here, not shimmed.
require_once $root.'/web/includes/log_redact.php';

if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

// The user object only has to answer System(): None/View/Edit.
class FakeUser {
  private $system;
  function __construct($system) { $this->system = $system; }
  function System() { return $this->system; }
}

// Pull one method out of a controller source by name and braces.
function extractMethod($source, $name) {
  if (!preg_match('/^[ \t]*(?:public |protected |private )?function '.preg_quote($name, '/').'\s*\([^\n]*\)\s*\{/m', $source, $m, PREG_OFFSET_CAPTURE)) {
    return null;
  }
  $start = $m[0][1];
  $open = $start + strlen($m[0][0]) - 1;
  $depth = 0;
  $len = strlen($source);
  for ($i = $open; $i < $len; $i++) {
    if ($source[$i] === '{') {
      $depth++;
    } else if ($source[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($source, $start, $i - $start + 1);
    }
  }
  return null;
}

// The extracted actions pull their helpers in with relative require_once calls
// that resolve against tests/php once eval'd; the test supplies the fakes.
function stripRequires($source) {
  return preg_replace('/^[ \t]*require_once [^;\n]*;\s*$/m', '', $source);
}

// The real ZM\Event and ZM\Storage are not loaded; these are the pieces the
// extracted actions use.
eval('namespace ZM;
  function Debug($s) {}
  class Event {
    private $row;
    function __construct($row = null) { $this->row = is_array($row) ? $row : array(); }
    function canView() { return true; }
    function Path() { return "/events/1/1"; }
  }
  class Storage {
    private $row;
    function __construct($row = null) { $this->row = is_array($row) ? $row : array(); }
    function disk_total_space() { return 1; }
    function disk_used_space() { return 1; }
  }');

$appSource = file_get_contents($root.'/web/api/app/Controller/AppController.php');
$hideSource = extractMethod($appSource, 'hideStorageSecrets');
// On the original tree the helper does not exist: the class simply has no
// method, and the checks below fail instead of dying on an eval error.
$hidePublic = $hideSource === null ? '' : str_replace('protected function', 'public function', stripRequires($hideSource));

eval('class HideHarness { '.$hidePublic.' }');

function hide_row($row) {
  try {
    $h = new HideHarness();
    return $h->hideStorageSecrets($row);
  } catch (Throwable $e) {
    return 'MISSING';
  }
}

class FakeStorageModel {
  public $recursive;
  public $primaryKey = 'Id';
  public $id;
  public $row = array();
  function exists($id = null) { return true; }
  function schema() { return array('Id' => 1, 'Name' => 1, 'Path' => 1, 'Url' => 1); }
  function find($type, $options = array()) {
    if ($type === 'all') return array(array('Storage' => $this->row));
    if ($type === 'first') return array('Storage' => $this->row);
    return array();
  }
}

class FakeStorageRequest {
  public $params = array('named' => array());
  function query($k) { return null; }
}

#[AllowDynamicProperties]
class FakeStorageBase {
  public $Storage;
  public $request;
  public $Components;
  public $FilterComponent;
  public $out = array();
  function __construct() {
    $this->Storage = new FakeStorageModel();
    $this->request = new FakeStorageRequest();
  }
  function set($v) { $this->out = $v; }
}

$storageSource = file_get_contents($root.'/web/api/app/Controller/StorageController.php');
$storageIndex = extractMethod($storageSource, 'index');
$storageView = extractMethod($storageSource, 'view');
eval('class StorageHarness extends FakeStorageBase { '.$hidePublic.' '.stripRequires($storageIndex).' '.stripRequires($storageView).' }');

function run_storage($user, $row, $method, $id = 1) {
  $GLOBALS['user'] = $user;
  $h = new StorageHarness();
  $h->Storage->row = $row;
  try {
    if ($method === 'view') $h->view($id); else $h->index();
  } catch (Throwable $e) {
    return 'THREW: '.$e->getMessage();
  }
  return $h->out;
}

class FakeEventModel {
  public $recursive;
  public $primaryKey = 'Id';
  public $id;
  public $row = array();
  public $storage = array();
  function exists($id = null) { return true; }
  function unbindModel($m) {}
  function fileExists($e) { return 0; }
  function fileSize($e) { return 0; }
  function find($type, $options = array()) {
    if ($type === 'first') return array('Event' => $this->row, 'Storage' => $this->storage);
    if ($type === 'neighbors') return array();
    return array();
  }
}

class FakeFrameModel {
  function findByEventid($id) { return null; }
  function findByEventidAndType($id, $type) { return null; }
}

class FakeEventsRequest {
  public $params = array('named' => array());
  function query($k) { return null; }
}

#[AllowDynamicProperties]
class FakeEventsBase {
  public $Event;
  public $Frame;
  public $Config;
  public $request;
  public $out = array();
  function __construct() {
    $this->Event = new FakeEventModel();
    $this->request = new FakeEventsRequest();
  }
  function loadModel($m) {
    if ($m === 'Frame') $this->Frame = new FakeFrameModel();
  }
  function set($v) { $this->out = $v; }
  function viewableMonitorCondition($field) { return array($field => array(1)); }
}

$eventsSource = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
$eventsView = extractMethod($eventsSource, 'view');
eval('class EventsHarness extends FakeEventsBase { '.$hidePublic.' '.stripRequires($eventsView).' }');

function run_events($user, $storageRow) {
  $GLOBALS['user'] = $user;
  $h = new EventsHarness();
  $h->Event->row = array('Id' => 1, 'MonitorId' => 1);
  $h->Event->storage = $storageRow;
  try {
    $h->view(1);
  } catch (Throwable $e) {
    return 'THREW: '.$e->getMessage();
  }
  return $h->out;
}

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

$secretUrl = 's3://AKIAKEY:SECRETKEY@host/bucket';
$cleanUrl = 's3://host/bucket';
$row = array('Id' => 1, 'Name' => 'S3 area', 'Path' => '/mnt/s3', 'Url' => $secretUrl);
$viewUser = new FakeUser('View');
$editUser = new FakeUser('Edit');
$noneUser = new FakeUser('None');

// hideStorageSecrets(): what the three endpoints share.
$GLOBALS['user'] = $viewUser;
$got = hide_row($row);
check('hideStorageSecrets: System View strips the s3 secret', is_array($got) ? $got['Url'] : $got, $cleanUrl);
check('hideStorageSecrets: System View keeps Name and Path', is_array($got) ? array($got['Name'], $got['Path']) : $got, array('S3 area', '/mnt/s3'));

$GLOBALS['user'] = $editUser;
$got = hide_row($row);
check('hideStorageSecrets: System Edit keeps the s3 secret', is_array($got) ? $got['Url'] : $got, $secretUrl);

$GLOBALS['user'] = $noneUser;
$got = hide_row($row);
check('hideStorageSecrets: System None strips the s3 secret', is_array($got) ? $got['Url'] : $got, $cleanUrl);

$GLOBALS['user'] = null;
$got = hide_row($row);
check('hideStorageSecrets: no user (auth off) keeps the s3 secret', is_array($got) ? $got['Url'] : $got, $secretUrl);

$GLOBALS['user'] = $viewUser;
$empty = array('Id' => 2, 'Name' => 'Local', 'Path' => '/mnt/local', 'Url' => '');
check('hideStorageSecrets: an empty Url is left alone', hide_row($empty), $empty);
check('hideStorageSecrets: a non-array is returned as is', hide_row('not a row'), 'not a row');

// StorageController::index() and view().
$out = run_storage($viewUser, $row, 'index');
check('storage index: System View strips the s3 secret', isset($out['storage'][0]['Storage']['Url']) ? $out['storage'][0]['Storage']['Url'] : $out, $cleanUrl);
check('storage index: other columns are unchanged',
  isset($out['storage'][0]['Storage']['Name']) ? array($out['storage'][0]['Storage']['Name'], $out['storage'][0]['Storage']['Path']) : $out,
  array('S3 area', '/mnt/s3'));

$out = run_storage($editUser, $row, 'index');
check('storage index: System Edit keeps the s3 secret', isset($out['storage'][0]['Storage']['Url']) ? $out['storage'][0]['Storage']['Url'] : $out, $secretUrl);

$out = run_storage($viewUser, $row, 'view');
check('storage view: System View strips the s3 secret', isset($out['storage']['Storage']['Url']) ? $out['storage']['Storage']['Url'] : $out, $cleanUrl);

$out = run_storage($editUser, $row, 'view');
check('storage view: System Edit keeps the s3 secret', isset($out['storage']['Storage']['Url']) ? $out['storage']['Storage']['Url'] : $out, $secretUrl);

// EventsController::view(): the 'Storage' row Cake joins onto the event.
$out = run_events($viewUser, $row);
check('events view: System View strips the s3 secret',
  isset($out['event']['Storage']['Url']) ? $out['event']['Storage']['Url'] : $out, $cleanUrl);

$out = run_events($editUser, $row);
check('events view: System Edit keeps the s3 secret',
  isset($out['event']['Storage']['Url']) ? $out['event']['Storage']['Url'] : $out, $secretUrl);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
