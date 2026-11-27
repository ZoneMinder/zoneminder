<?php
// API host/daemonCheck answered any authenticated user, including one with System=None.
// Run as: php tests/php/test_api_daemoncheck_permission.php [root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$source = file_get_contents("$root/web/api/app/Controller/HostController.php");
$start = strpos($source, 'public function daemonCheck(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);
class UnauthorizedException extends Exception {}
function __($s) { return $s; }
$bin = sys_get_temp_dir().'/zmdc_stub_'.getmypid();
mkdir($bin);
file_put_contents("$bin/zmdc.pl", "#!/bin/sh\necho running\n");
chmod("$bin/zmdc.pl", 0755);
define('ZM_PATH_BIN', $bin);
eval('namespace ZM; function Error($m) {}');
class FakeUser {
  private $s;
  function __construct($s) { $this->s = $s; }
  function System() { return $this->s; }
}
eval('class CheckHarness { public $vars; function set($v) { $this->vars = $v; } '.$method.' }');
function attempt($u) {
  global $user;
  $user = $u;
  $h = new CheckHarness();
  try {
    $h->daemonCheck('zmc');
  } catch (UnauthorizedException $e) {
    return 'refused';
  }
  return 'answered';
}
check('System None is refused', attempt(new FakeUser('None')), 'refused');
check('System View is answered', attempt(new FakeUser('View')), 'answered');
check('System Edit is answered', attempt(new FakeUser('Edit')), 'answered');
check('auth off is answered', attempt(null), 'answered');
unlink("$bin/zmdc.pl");
rmdir($bin);
exit($failures ? 1 : 0);
