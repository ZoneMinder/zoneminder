<?php
// Tests the CMD_SEEK/CMD_MAXFPS message ajax/stream.php sends zms (refs #4186).
//
// zms reads the message as fixed bytes: MSG_CMD, the command, then whole
// seconds and microseconds, each a big-endian uint32. The fraction is rounded,
// not truncated: 2.3 - 2 is 0.29999999999999982, which truncated to 299999us
// and seeked to the frame before the one at 2.3s.
//
// Run: php tests/php/test_stream_seek_pack.php   (from the repo root)

$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
require_once($root.'/web/includes/stream_set_event.php');

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

echo "packSecondsMessage\n";
$msg = packSecondsMessage(1, 14, 2.3);
check('13 bytes, the size of MsgCommand with two uint32s', strlen($msg), 13);
$cmd = unpack('lmsg_type/ccommand/Nsec/Nusec', $msg);
check('msg_type', $cmd['msg_type'], 1);
check('command', $cmd['command'], 14);

$cases = array(
  '2.3' => array(2, 300000),
  '7.3' => array(7, 300000),
  '12.7' => array(12, 700000),
  '0' => array(0, 0),
  '5' => array(5, 0),
  '3.35' => array(3, 350000),
  '2147.5' => array(2147, 500000),
  '0.9999999' => array(1, 0),
  '4000.25' => array(4000, 250000),
);
foreach ($cases as $seconds => $want) {
  $packed = unpack('lmsg_type/ccommand/Nsec/Nusec', packSecondsMessage(1, 14, $seconds));
  check("$seconds sec", $packed['sec'], $want[0]);
  check("$seconds usec", $packed['usec'], $want[1]);
}

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
