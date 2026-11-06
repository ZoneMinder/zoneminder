<?php
// ZoneMinder requires PHP 8.0 or newer: the vendored firebase/php-jwt 7 needs it.
// This checks that the requirement is declared everywhere it is enforced, and
// that no PHP 4/5/7 compatibility branch comes back into the web code.
//
// Run: php tests/php/test_php_minimum.php
$root = __DIR__.'/../..';

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok   $name\n";
  } else {
    $failures++;
    echo "FAIL $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

$composer = json_decode(file_get_contents("$root/web/composer.json"), true);
check('composer.json requires php >=8.0', $composer['require']['php'] ?? null, '>=8.0');
check('password-compat (a PHP 5.5 polyfill) is not required',
  isset($composer['require']['ircmaxell/password-compat']), false);
check('composer platform check rejects PHP < 8.0',
  strpos(file_get_contents("$root/web/vendor/composer/platform_check.php"), 'PHP_VERSION_ID >= 80000') !== false, true);

check('rpm requires php-common >= 8.0',
  (bool)preg_match('/^Requires: php-common >= 8\.0$/m', file_get_contents("$root/distros/redhat/zoneminder.spec")), true);
foreach (array('ubuntu2004', 'beowulf') as $distro) {
  check("$distro deb requires php-mysql (>= 2:8.0)",
    strpos(file_get_contents("$root/distros/$distro/control"), 'php-mysql (>= 2:8.0)') !== false, true);
}

// A comparison of the running PHP version against anything below 8 is dead code.
$old_checks = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/web", FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
  $path = substr($file->getPathname(), strlen("$root/"));
  if (substr($path, -4) != '.php' or preg_match('#^web/(vendor|api/lib|api/app/vendor|api/app/Plugin)/#', $path)) continue;
  foreach (file($file->getPathname()) as $n => $line) {
    if (preg_match('/(phpversion\(\)|PHP_VERSION)\s*,\s*[\'"][4-7]\.|PHP_MAJOR_VERSION\s*<\s*8/', $line))
      $old_checks[] = "$path:".($n + 1);
  }
}
check('no PHP 4/5/7 version checks in web code', $old_checks, array());

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
