<?php
// EncoderTemplatesController::index() put ?Encoder[a][b]=1 straight into the find() conditions.
// Cake wrote the inner list into the SQL as the word Array and MySQL answered "Unknown column
// 'Array'", a 500. A nested list is now a 400; a name, or a list of names, still filters.
//
// The controller can't be loaded without CakePHP, so it is run against stubs.
// Run as: php tests/php/test_api_encoder_templates_index_nested_encoder.php [root]   (root defaults to the repo)

error_reporting(E_ALL);
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

class App { public static function uses($class, $package) {} }
class BadRequestException extends Exception {}
function __($s) { return $s; }
#[AllowDynamicProperties]
class AppController {
  public $request;
  public $Components;
  public $vars = array();
  public function set($vars) { $this->vars = $vars; }
}
class FakeRequest {
  public $params = array('named' => array());
  public $query;
  function __construct($query) { $this->query = $query; }
}
class FakeModel {
  public $recursive;
  public $conditions = null;
  function schema() { return array(); }
  function find($type, $options) { $this->conditions = $options['conditions']; return array(); }
}
require_once $root.'/web/api/app/Controller/EncoderTemplatesController.php';

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function run($query) {
  $c = new EncoderTemplatesController();
  $c->request = new FakeRequest($query);
  $c->EncoderTemplate = new FakeModel();
  try {
    $c->index();
  } catch (BadRequestException $e) {
    return 'BadRequestException';
  }
  return $c->EncoderTemplate->conditions;
}

check('a nested list is a 400', run(array('Encoder' => array('a' => array('b' => '1')))), 'BadRequestException');
check('a list holding a list is a 400', run(array('Encoder' => array(array('1')))), 'BadRequestException');
check('a name filters', run(array('Encoder' => 'libx264')), array('EncoderTemplate.Encoder' => 'libx264'));
check('a list of names filters', run(array('Encoder' => array('libx264', 'libx265'))), array('EncoderTemplate.Encoder' => array('libx264', 'libx265')));
check('no Encoder leaves the conditions empty', run(array()), array());

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
