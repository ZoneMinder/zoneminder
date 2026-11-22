<?php
// The CMD_SET_EVENT stream command: the event view tells zms which event is
// next or prev in the filter the user is viewing (refs #4186). zms loads the
// target without checking permissions itself, so ajax/stream.php checks the
// target's monitor first. The id checked and the id sent must be the same id.

// An event id from the request as an int, or null if it is not one. Accepts
// only digits, and only values this PHP's int can hold: on 32-bit PHP
// intval('2147483648') is 2147483647, so the permission check would see one
// event and zms would be sent another.
function setEventIdFromRequest($value) {
  if (is_int($value)) return $value >= 0 ? $value : null;
  if (!is_string($value) || !preg_match('/^[0-9]{1,20}$/', $value)) return null;
  $value = ltrim($value, '0');
  if ($value === '') return 0;
  $max = (string)PHP_INT_MAX;
  if (strlen($value) > strlen($max) || (strlen($value) == strlen($max) && strcmp($value, $max) > 0)) return null;
  return intval($value);
}

// Big-endian uint64 as two 32-bit words, so it works on 32-bit PHP, where
// pack('J') is not available.
function packEventId64($id) {
  if (PHP_INT_SIZE >= 8) {
    return pack('NN', ($id >> 32) & 0xFFFFFFFF, $id & 0xFFFFFFFF);
  }
  return pack('NN', 0, $id);
}

// The low 48 bits, big-endian: a 16-bit then a 32-bit word. zms compares the
// same 48 bits of the event it is playing.
function packEventId48($id) {
  if (PHP_INT_SIZE >= 8) {
    return pack('nN', ($id >> 32) & 0xFFFF, $id & 0xFFFFFFFF);
  }
  return pack('nN', 0, $id);
}

// The message for zms: MSG_CMD, the command, the direction (0 next, 1 prev),
// the target (0 when the filter has no neighbour) and the event it was computed
// for. See MsgCommand in src/zm_stream.h.
function packSetEventMessage($msg_cmd, $command, $prev, $event_id, $source_event_id) {
  return pack('lcC', $msg_cmd, $command, $prev ? 1 : 0)
    .packEventId64($event_id)
    .packEventId48($source_event_id);
}
?>
