<?php
// Cake sets loaded components on the controller as undeclared properties, and the API controllers
// do the same with $this->FilterComponent. PHP 8.2 deprecates creating such a property unless the
// class carries #[AllowDynamicProperties]; AppController is the one parent they all share.
// AppController is loaded from the source against stubs of Cake's Controller (same __set as Cake 2).
// Run as: php tests/php/test_api_controller_dynamic_properties.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

class App { public static function uses() {} }
trait CrudControllerTrait {}
class Controller {
  public function __set($name, $value) { $this->{$name} = $value; }
}
require $root.'/web/api/app/Controller/AppController.php';

class ProbeController extends AppController {
  public function load() {
    $this->FilterComponent = new stdClass();
    $this->RequestHandler = new stdClass();
  }
}

$names = array();
foreach ((new ReflectionClass('AppController'))->getAttributes() as $attribute) $names[] = $attribute->getName();
check('AppController carries AllowDynamicProperties', in_array('AllowDynamicProperties', $names, true), true);

$deprecations = array();
set_error_handler(function ($no, $str) use (&$deprecations) {
  if ($no === E_DEPRECATED) $deprecations[] = $str;
  return true;
});
$c = new ProbeController();
$c->load();
restore_error_handler();
check('setting a component on a controller raises no deprecation', $deprecations, array());
check('the property is still set', get_class($c->FilterComponent), 'stdClass');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
