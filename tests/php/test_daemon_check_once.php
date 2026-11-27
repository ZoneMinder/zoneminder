<?php
// Every page asks whether ZoneMinder is running at least twice (the navbar status and
// skin.js.php), and each daemonCheck() started zmdc.pl, a perl process. The plain check
// is now made once a request, and again after packageControl() changes the run state.
// Checks of a single daemon still ask zmdc.pl each time.
//
// functions.php can't be included without the rest of the web bootstrap, so the
// functions are taken from the source.
//
// Run as: php tests/php/test_daemon_check_once.php

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

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
foreach (array('daemonCheck', 'packageControl') as $name) {
  if (!preg_match('/^function '.$name.'\(.*?^}\n/ms', $source, $m)) {
    echo "FAIL - could not find $name in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}

// Stand-ins for zmdc.pl and zmpkg.pl that log each run; zmdc.pl answers with the state file.
$bin = sys_get_temp_dir().'/zm_daemon_check_'.getmypid();
mkdir($bin);
define('ZM_PATH_BIN', $bin);
file_put_contents("$bin/state", 'running');
file_put_contents("$bin/zmdc.pl", "#!/bin/sh\necho \"\$*\" >> $bin/calls\ncat $bin/state\n");
file_put_contents("$bin/zmpkg.pl", "#!/bin/sh\necho stopped > $bin/state\n");
chmod("$bin/zmdc.pl", 0755);
chmod("$bin/zmpkg.pl", 0755);
function calls() {
  $file = ZM_PATH_BIN.'/calls';
  return file_exists($file) ? count(file($file)) : 0;
}

check('first check runs zmdc.pl', daemonCheck(), 1);
check('second check gives the same answer', daemonCheck(), 1);
check('zmdc.pl ran once for both', calls(), 1);

daemonCheck('zmc', '-m 1');
daemonCheck('zmc', '-m 1');
check('checks of one daemon still run zmdc.pl each time', calls(), 3);

packageControl('stop');
check('after a run state change zmdc.pl is asked again', daemonCheck(), 0);
check('zmdc.pl ran once more', calls(), 4);
check('a stopped answer is kept too', daemonCheck(), 0);
check('zmdc.pl was not run for it', calls(), 4);

array_map('unlink', glob("$bin/*"));
rmdir($bin);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
