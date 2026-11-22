<?php
// ConfigsController::edit() must write the row named in the URL, never one named in the
// request body, and must only let Value through to the database.
//
// The controller can't be loaded without CakePHP, so edit() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_configs_edit_url_row.php [root]   (root defaults to the repo)

namespace {

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

foreach (array('BadRequestException', 'UnauthorizedException', 'NotFoundException', 'ForbiddenException') as $e) {
  if (!class_exists($e)) eval("class $e extends Exception {}");
}
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException('Method Not Allowed');
  }
}

class FakeResponse {
  function statusCode($c = null) {}
  function httpCodes($c = null) {}
}

class FakeConfigModel {
  public $id = null;
  public $alias = 'Config';
  public $primaryKey = 'Name';
  public $recursive = 0;
  public $validationErrors = array();
  public $table = array(
    'ZM_WEB_TITLE' => array('Name' => 'ZM_WEB_TITLE', 'System' => 0, 'Readonly' => 0, 'Value' => 'a'),
    'ZM_PATH_FFMPEG' => array('Name' => 'ZM_PATH_FFMPEG', 'System' => 1, 'Readonly' => 0, 'Value' => '/usr/bin/ffmpeg'),
  );
  public $writes = array();
  function exists($id = null) { return true; }
  function find($type, $options = array()) {
    $id = $this->id;
    if (isset($options['conditions']['Config.'.$this->primaryKey])) {
      $id = $options['conditions']['Config.'.$this->primaryKey];
    }
    $row = isset($this->table[$id]) ? $this->table[$id] : array();
    return array('Config' => $row);
  }
  function save($data = null, $validate = true, $fieldList = array()) {
    $row = isset($data['Config']) && is_array($data['Config']) ? $data['Config'] : (array)$data;
    $name = array_key_exists('Name', $row) ? $row['Name'] : $this->id;
    $fields = array_keys($row);
    if (is_array($fieldList) && count($fieldList)) {
      $fields = array_values(array_intersect($fields, $fieldList));
    }
    $fields = array_values(array_diff($fields, array('Name')));
    $this->writes[] = array('name' => $name, 'fields' => $fields);
    return true;
  }
  function saveField($f, $v) { return true; }
}

function extract_edit($file) {
  $source = file_get_contents($file);
  $start = strpos($source, 'public function edit(');
  if ($start === false) return null;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$user = new class {
  function System() { return 'Edit'; }
};

$file = $root.'/web/api/app/Controller/ConfigsController.php';
$edit = is_file($file) ? extract_edit($file) : null;
if ($edit === null) {
  echo "FAIL - no edit() in $file\n";
  exit(1);
}

$class = 'ConfigsEditHarness';
eval('class '.$class.' { public $request, $response, $Config, $vars = array();
  function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
  function view($id = null) {}
  protected function pinRequestId($model, $id) {
    $alias = $model->alias;
    $key = $model->primaryKey;
    if (isset($this->request->data[$alias]) and is_array($this->request->data[$alias])) {
      unset($this->request->data[$alias][$key]);
    }
    unset($this->request->data[$key]);
    $model->id = $id;
  }
  protected function requestField($alias, $field) {
    $data = $this->request->data;
    if (!empty($data[$alias])) {
      $data = is_array($data[$alias]) ? $data[$alias] : array();
    }
    return isset($data[$field]) ? $data[$field] : null;
  }
  '.$edit.' }');

function run_edit($class, $method, $id, $data) {
  $h = new $class();
  $h->request = new FakeRequest($method, $data);
  $h->response = new FakeResponse();
  $h->Config = new FakeConfigModel();
  $thrown = null;
  try {
    $h->edit($id);
  } catch (Throwable $e) {
    $thrown = $e;
  }
  return array($h, $thrown);
}

list($h1) = run_edit($class, 'PUT', 'ZM_WEB_TITLE', array('Config' => array('Name' => 'ZM_WEB_TITLE', 'Value' => 'b')));
check('1: one row written', count($h1->Config->writes), 1);
check('1: row written is the URL id', $h1->Config->writes[0]['name'], 'ZM_WEB_TITLE');
check('1: only Value written', $h1->Config->writes[0]['fields'], array('Value'));
check('1: answers Saved', isset($h1->vars['message']) ? $h1->vars['message'] : null, 'Saved');

list($h2) = run_edit($class, 'PUT', 'ZM_WEB_TITLE', array('Config' => array('Name' => 'ZM_PATH_FFMPEG', 'Value' => '/bin/evil')));
check('2: one row written', count($h2->Config->writes), 1);
check('2: row written is the URL id, not the body name', $h2->Config->writes[0]['name'], 'ZM_WEB_TITLE');
check('2: body name is not used', $h2->Config->writes[0]['name'] === 'ZM_PATH_FFMPEG', false);

list($h3) = run_edit($class, 'PUT', 'ZM_WEB_TITLE', array('Config' => array(
  'Name' => 'ZM_WEB_TITLE', 'Value' => 'c', 'System' => 0, 'Readonly' => 0, 'Type' => 'x',
)));
check('3: one row written', count($h3->Config->writes), 1);
check('3: row written is the URL id', $h3->Config->writes[0]['name'], 'ZM_WEB_TITLE');
check('3: System not written', in_array('System', $h3->Config->writes[0]['fields'], true), false);
check('3: Readonly not written', in_array('Readonly', $h3->Config->writes[0]['fields'], true), false);
check('3: Type not written', in_array('Type', $h3->Config->writes[0]['fields'], true), false);
check('3: only Value written', $h3->Config->writes[0]['fields'], array('Value'));

list($h4, $thrown) = run_edit($class, 'PUT', 'ZM_PATH_FFMPEG', array('Config' => array('Name' => 'ZM_PATH_FFMPEG', 'Value' => '/bin/evil')));
check('4: system config still forbidden', $thrown ? get_class($thrown) : null, 'ForbiddenException');
check('4: nothing written', count($h4->Config->writes), 0);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
}
