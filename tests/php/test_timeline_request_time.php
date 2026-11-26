<?php
// A garbage minTime/midTime/maxTime must be ignored, not turned into 1970.
require_once __DIR__.'/../../web/skins/classic/includes/timeline_functions.php';

$fail = 0;
function check($name, $got, $want, &$fail) {
  $ok = ($got === $want);
  echo ($ok ? 'ok   ' : 'FAIL ').$name.' (got '.var_export($got, true).")\n";
  if (!$ok) $fail++;
}

check('date time', validTimelineTime('2026-11-16 10:00:00'), '2026-11-16 10:00:00', $fail);
check('relative', validTimelineTime('-1 day'), '-1 day', $fail);
check('garbage', validTimelineTime('garbage'), null, $fail);
check('bad month', validTimelineTime('2026-13-45'), null, $fail);
check('empty', validTimelineTime(''), null, $fail);
check('array', validTimelineTime(array('x')), null, $fail);
exit($fail ? 1 : 0);
