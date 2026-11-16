<?php
/*
 * Regression test for GHSA-9gc6-9xg2-5636.
 *
 * StatesController::packageControl() runs zmpkg.pl (start/stop/switch run state)
 * with the request's state name. change() is its only intended caller and gates
 * on System=Edit first. While packageControl() was public, CakePHP routed
 * GET /api/states/packageControl/<cmd> straight to it, skipping that check, so
 * any user admitted by beforeFilter() (System != None, i.e. System=View) could
 * control the daemon. The fix makes packageControl() protected: a protected
 * controller method is not routable, so the direct route raises
 * PrivateActionException while change() can still call it internally.
 *
 * This test includes the real controller (CakePHP base/App stubbed so no method
 * body runs) and uses reflection to assert packageControl() is protected and
 * change() stays public. Run standalone: php test_states_package_control_protected.php
 */

error_reporting(E_ALL & ~E_DEPRECATED);

$checks = 0;
$failures = 0;
function check($cond, $desc) {
  global $checks, $failures;
  $checks++;
  if ($cond) {
    echo "ok   - $desc\n";
  } else {
    $failures++;
    echo "FAIL - $desc\n";
  }
}

// Minimal stubs so StatesController.php can be included without the CakePHP
// framework. Only the class declaration is evaluated at include time; no method
// body (which references Configure, exec, the exception classes, ...) runs.
if (!class_exists('App')) {
  class App { public static function uses($class, $package) {} }
}
if (!class_exists('AppController')) {
  class AppController {}
}

$controller = null;
foreach (array(
  __DIR__.'/../../web/api/app/Controller/StatesController.php',
  __DIR__.'/../../../web/api/app/Controller/StatesController.php',
) as $candidate) {
  if (is_file($candidate)) { $controller = $candidate; break; }
}
check($controller !== null, 'found StatesController.php');
if ($controller === null) {
  echo "1..$checks\nBail out! controller source not found\n";
  exit(1);
}

require $controller;
check(class_exists('StatesController'), 'StatesController class loaded');

$rc = new ReflectionClass('StatesController');

check($rc->hasMethod('packageControl'), 'packageControl() exists');
$pkg = $rc->getMethod('packageControl');

// The fix: packageControl must be protected, not public/private.
check($pkg->isProtected(), 'packageControl() is protected');
check(!$pkg->isPublic(), 'packageControl() is NOT public (not directly routable)');

// change() is the intended public entry point and must stay routable.
check($rc->hasMethod('change'), 'change() exists');
$change = $rc->getMethod('change');
check($change->isPublic(), 'change() is public (the intended, System=Edit-gated caller)');

// change() still calls packageControl internally (the fix must not orphan it).
$src = file_get_contents($controller);
check(preg_match('/function\s+change\s*\(/', $src) === 1, 'change() is defined');
check(strpos($src, '$this->packageControl(') !== false,
  'change() still calls $this->packageControl()');

// And change() gates on System=Edit before that call (object or array $user form).
$changeStart = strpos($src, 'function change');
$changeBody = substr($src, $changeStart);
$callPos = strpos($changeBody, '$this->packageControl(');
$beforeCall = substr($changeBody, 0, $callPos);
check(preg_match("/(System\(\)|\['System'\]|\[\"System\"\])\s*==\s*'Edit'/", $beforeCall) === 1,
  'change() checks System == Edit before calling packageControl()');

echo "1..$checks\n";
if ($failures) {
  echo "# $failures of $checks checks failed\n";
  exit(1);
}
echo "# all $checks checks passed\n";
