<?php
// Security regression test: an id taken from the request must not build an object from the
// request.
//
// ZM_Object::__construct() treats an array as the object's row. validInt() and
// validCardinal() used to return an array as they got it (preg_replace() does), so
// new ZM\Filter(validInt($_REQUEST['Id'])) with Id[Id]=3&Id[UserId]=<me> built a filter
// claiming to be mine; canDelete() checked that UserId and delete() removed filter 3,
// another user's. The same held for events (MonitorId), users and the rest.
//
// 1. validInt() and validCardinal() give '' for anything that isn't a single value.
// 2. No web code builds an object straight from $_REQUEST/$_GET/$_POST.
//
// Run as: php tests/php/test_request_ids_scalar.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$web = __DIR__.'/../../web';
$source = file_get_contents($web.'/includes/functions.php');
foreach (array('validInt', 'validCardinal') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $source, $m)) {
    echo "FAIL - could not find $fn in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}

check('validInt keeps a number', validInt('42'), '42');
check('validInt keeps a negative number', validInt('-7'), '-7');
check('validInt strips non-digits', validInt('4x2'), '42');
check('validInt of an int', validInt(42), '42');
check('validInt of an array is empty', validInt(array('Id' => '3', 'UserId' => '4')), '');
check('validInt of a list is empty', validInt(array('3')), '');
check('validInt of null is empty', validInt(null), '');
check('validCardinal keeps a number', validCardinal('42'), '42');
check('validCardinal drops a sign', validCardinal('-7'), '7');
check('validCardinal of an array is empty', validCardinal(array('Id' => '3', 'MonitorId' => '1')), '');
check('validCardinal of a nested array is empty', validCardinal(array(array('3'))), '');
check('array_map over a list still validates each element',
  array_values(array_filter(array_map('validCardinal', array('1', 'x2', array('3'))))), array('1', '2'));

// No `new ZM\Something($_REQUEST[...])`: the id has to go through validCardinal() or
// validInt() first.
$offenders = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($web, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
  $path = $file->getPathname();
  if (substr($path, -4) !== '.php' or strpos($path, '/api/lib/') !== false or strpos($path, '/vendor/') !== false) continue;
  foreach (file($path) as $n => $line) {
    if (preg_match('/new\s+(\\\\?ZM\\\\)?[A-Z]\w*\(\s*\$_(REQUEST|GET|POST)\[/', $line)) {
      $offenders[] = substr($path, strlen($web) + 1).':'.($n + 1).': '.trim($line);
    }
  }
}
check('no object is built straight from the request', implode("\n", $offenders), '');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
