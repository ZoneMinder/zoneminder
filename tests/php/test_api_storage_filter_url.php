<?php
// StorageController::index() must not let a user without System edit filter
// storage areas on Url, which can carry credentials, while System == 'Edit'
// and an unauthenticated $user (null) keep the old behaviour. The named
// parameters reach FilterComponent::buildFilter(), whose field allow-list is
// built from the Storage schema minus Url for everyone else, so an Url filter
// is a 400 instead of a working query.
//
// Cake's App, Component and BadRequestException are stubbed; index() is read
// from the controller source and eval'd so this runs from a checkout:
// php tests/php/test_api_storage_filter_url.php [root]

class App {
  public static function uses($class, $package) {}
}
class Component {}
class BadRequestException extends Exception {}

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$component = $root.'/web/api/app/Controller/Component/FilterComponent.php';
$file = $root.'/web/api/app/Controller/StorageController.php';
if (!is_file($component)) { echo "FAIL - FilterComponent not found at $component\n"; exit(1); }
if (!is_file($file)) { echo "FAIL - controller not found at $file\n"; exit(1); }

require_once $component;

$source = file_get_contents($file);

function extractMethod($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  if ($start === false) { echo "FAIL - $name() not found\n"; exit(1); }
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$index = extractMethod($source, 'index');
// The require is relative to the controller directory, which is not where
// eval'd code resolves __DIR__, so drop it; the stub find() returns no rows
// and the storage loop stays empty.
$index = preg_replace('/^[ \t]*require_once[^\n]*\n/m', '', $index);
check('index() storage require is removed for the test', strpos($index, 'includes/Storage.php'), false);

class FakeRequest {
  public $params;
  function __construct($named) {
    $this->params = array('named' => $named);
  }
}

class FakeStorage {
  public $recursive = 0;
  public $findCount = 0;
  function schema() {
    return array(
      'Id' => array(),
      'Name' => array(),
      'Path' => array(),
      'Url' => array(),
      'Type' => array(),
      'DoNotPurgeEvents' => array(),
    );
  }
  function find($type = 'all', $options = array()) {
    $this->findCount++;
    return array();
  }
}

class FakeComponents {
  function load($name) {
    return new FilterComponent();
  }
}

class FakeUser {
  private $system;
  function __construct($system) {
    $this->system = $system;
  }
  function System() {
    return $this->system;
  }
}

eval('class FakeStorageCtl {
  public $request;
  public $Components;
  public $Storage;
  public $FilterComponent;
  public $out;
  public $called = false;
  function set($a) { $this->out = $a; $this->called = true; }
  '.$index.'
}');

function run($named, $system) {
  global $user;
  $user = ($system === null) ? null : new FakeUser($system);
  $c = new FakeStorageCtl();
  $c->request = new FakeRequest($named);
  $c->Components = new FakeComponents();
  $c->Storage = new FakeStorage();
  try {
    $c->index();
    return $c->called ? 'ok' : 'index() did not call set()';
  } catch (BadRequestException $e) {
    return '400 '.$e->getMessage();
  }
}

check('Url filter as View is a 400',
  run(array('Url' => 'a'), 'View'), '400 Unknown filter field: Url');
check('Url LIKE filter as View is a 400',
  run(array('Url LIKE' => 's3'), 'View'), '400 Unknown filter field: Url');
check('Name filter as View is allowed',
  run(array('Name' => 'x'), 'View'), 'ok');
check('Url filter as Edit is allowed',
  run(array('Url' => 's3://bucket'), 'Edit'), 'ok');
check('Url filter with no user is allowed',
  run(array('Url' => 's3://bucket'), null), 'ok');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
