<?php
// Tests the CMD_SET_EVENT message ajax/stream.php sends zms (refs #4186).
//
// The event view tells zms which event comes next in the user's filter. zms
// reads the message as fixed bytes (EventStream::DecodeSetEvent, tested in
// tests/zm_eventstream_hints.cpp), and loads the target without checking
// permissions, so stream.php checks the target first. The id it checks must be
// exactly the id it packs.
//
// Run: php tests/php/test_stream_set_event.php   (from the repo root)

require_once(__DIR__.'/../../web/includes/stream_set_event.php');

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

echo "setEventIdFromRequest\n";
check('digits', setEventIdFromRequest('634646'), 634646);
check('zero is the filter boundary', setEventIdFromRequest('0'), 0);
check('leading zeros', setEventIdFromRequest('0042'), 42);
check('an int', setEventIdFromRequest(17), 17);
check('missing', setEventIdFromRequest(null), null);
check('empty', setEventIdFromRequest(''), null);
check('negative', setEventIdFromRequest('-1'), null);
check('not a number', setEventIdFromRequest('12abc'), null);
check('whitespace', setEventIdFromRequest(' 12'), null);
check('an array', setEventIdFromRequest(array('5')), null);
check('PHP_INT_MAX fits', setEventIdFromRequest((string)PHP_INT_MAX), PHP_INT_MAX);
// On 32-bit PHP intval() would turn this into PHP_INT_MAX: the permission
// check would see one event and zms would load another.
check('one past PHP_INT_MAX is refused', setEventIdFromRequest(bcadd_one((string)PHP_INT_MAX)), null);
check('too many digits', setEventIdFromRequest('123456789012345678901'), null);

function bcadd_one($digits) {
  $i = strlen($digits) - 1;
  while ($i >= 0 && $digits[$i] == '9') { $digits[$i] = '0'; $i--; }
  if ($i < 0) return '1'.$digits;
  $digits[$i] = chr(ord($digits[$i]) + 1);
  return $digits;
}

echo "packSetEventMessage\n";
$msg = packSetEventMessage(3, 22, false, 634646, 634645);
check('20 bytes, the size of CmdMsg', strlen($msg), 20);
$cmd = unpack('lmsg_type/ccommand/Cdir', $msg);
check('msg_type', $cmd['msg_type'], 3);
check('command', $cmd['command'], 22);
check('next', $cmd['dir'], 0);
check('target, big-endian 64-bit', bin2hex(substr($msg, 6, 8)), '000000000009af16');
check('source, big-endian 48-bit', bin2hex(substr($msg, 14, 6)), '00000009af15');
check('prev', unpack('C', substr(packSetEventMessage(3, 22, true, 0, 1), 5, 1))[1], 1);
check('boundary target is zero', bin2hex(substr(packSetEventMessage(3, 22, false, 0, 1), 6, 8)), '0000000000000000');
if (PHP_INT_SIZE >= 8) {
  check('high word of a large target', bin2hex(packEventId64(0x0102030405060708)), '0102030405060708');
  check('source keeps its low 48 bits', bin2hex(packEventId48(0x0102030405060708)), '030405060708');
}

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
