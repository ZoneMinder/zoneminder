<?php
// daemonStatus() and zmcStatus() must build the same string zmdc.pl prints in
// its status output ("'zmcontrol.pl --id 5' running since ...").
// Run: php tests/php/test_daemon_status_match.php

$src = file_get_contents(__DIR__.'/../../web/includes/functions.php');

function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($src, $sig, $i - $sig + 1);
    }
  }
  return null;
}

function initDaemonStatus() {}
eval(extract_function($src, 'daemonStatus'));
eval(extract_function($src, 'zmcStatus'));

$daemon_status = "'zmc -m 5' running since 26/11/16 10:00:00, pid = 100, valid = 1\n".
  "'zmcontrol.pl --id 5' running since 26/11/16 10:00:00, pid = 101, valid = 1\n".
  "'zmc -d /dev/video0' running since 26/11/16 10:00:00, pid = 102, valid = 1\n";

$fail = 0;
function check($label, $ok) {
  global $fail;
  echo ($ok ? '  ok ' : '  FAIL ').$label."\n";
  if (!$ok) $fail++;
}

check('array args are joined with a space', daemonStatus('zmcontrol.pl', array('--id', 5)) === true);
check('string args still match', daemonStatus('zmc', '-m 5') === true);
check('a daemon that is not running is reported as stopped', daemonStatus('zmcontrol.pl', array('--id', 6)) === false);
check('zmcStatus matches a remote monitor', zmcStatus(array('Type' => 'Ffmpeg', 'Id' => 5)) === true);
check('zmcStatus matches a local monitor', zmcStatus(array('Type' => 'Local', 'Device' => '/dev/video0', 'Id' => 7)) === true);

exit($fail ? 1 : 0);
