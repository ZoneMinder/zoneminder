<?php
// Regression test for the failure classification in web/ajax/stream.php.
//
// The client restarts a stream only for a 'no_socket' failure, because
// restarting replaces the connkey and leaves any zms still running unreachable.
// A failure classified too harshly therefore orphans a live zms process; one
// classified too leniently leaves a dead stream on screen.
//
// This parses the classification out of stream.php rather than executing it:
// the script talks to unix sockets and a running zms, so it cannot be driven
// directly. What is worth pinning down is the mapping itself.
//
// Run as: php tests/php/test_stream_error_reason.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

$path = __DIR__ . '/../../web/ajax/stream.php';
$src = file_get_contents($path);
if ($src === false) {
  echo "Cannot read $path\n";
  exit(1);
}

// Every ajaxError() call must carry a classification, otherwise the client
// falls back to treating it as fatal and we are back to orphaning zms.
// Match each call up to the end of its statement, so a call that spans lines is
// still one item, and count the calls that carry a constant - counting bare
// STREAM_ERR_ occurrences instead would let the four define()s cover for four
// call sites that had lost their argument.
preg_match_all('/^\s*ajaxError\(.*?\);/ms', $src, $m);
$calls = count($m[0]);
$unclassified = array_filter($m[0], function($call) {
  return strpos($call, 'STREAM_ERR_') === false;
});
echo "ajaxError classification\n";
check('every ajaxError call is classified',
  $calls > 0 && !$unclassified, true);

// The four classes the client distinguishes.
foreach (array('NO_SOCKET' => 'no_socket', 'TIMEOUT' => 'timeout',
               'TRANSIENT' => 'transient', 'INVALID' => 'invalid') as $const => $value) {
  check("STREAM_ERR_$const is defined as '$value'",
    (bool)preg_match("/define\('STREAM_ERR_$const',\s*'$value'\)/", $src), true);
}

// A missing socket, and a send to a socket with no listener, are the only
// cases that mean zms is gone. These are the ones that may restart the stream.
echo "\nfatal classification\n";
check('missing socket file is no_socket',
  (bool)preg_match('/does not exist.*?STREAM_ERR_NO_SOCKET/s', $src), true);
check('socket_sendto failure is no_socket',
  (bool)preg_match('/socket_sendto\(.*?STREAM_ERR_NO_SOCKET/s', $src), true);

// A timeout must NOT be reported as a generic failure: zms is most likely
// alive, and restarting it is what orphaned the process.
echo "\nnon-fatal classification\n";
check('select timeout is reported as timeout, not transient',
  (bool)preg_match('/\$select_timed_out\s*\)\s*\{\s*ajaxError\(.*?STREAM_ERR_TIMEOUT/s', $src), true);
check('socket_create failure is transient',
  (bool)preg_match('/socket_create\(\).*?STREAM_ERR_TRANSIENT/s', $src), true);
check('socket_bind failure is transient',
  (bool)preg_match('/socket_bind\(.*?STREAM_ERR_TRANSIENT/s', $src), true);
check('bad request is invalid',
  (bool)preg_match('/No connkey or no command.*?STREAM_ERR_INVALID/s', $src), true);

// socket_recvfrom() returns false on failure, and false == 0 under switch's
// loose comparison, so a timed-out select lands on `case 0` rather than -1.
// If this ever stopped holding, the timeout branch would silently never run.
echo "\nphp switch semantics the timeout branch relies on\n";
$matched = null;
switch (false) {
  case -1: $matched = -1; break;
  case 0: $matched = 0; break;
  default: $matched = 'default'; break;
}
check('switch(false) matches case 0, not case -1', $matched, 0);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
