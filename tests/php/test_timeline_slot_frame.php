<?php
// getSlotFrame() must fall back to frame 1 when a slot has no usable frame,
// including when the frame lookup found nothing and stored false.
require_once __DIR__.'/../../web/skins/classic/includes/timeline_functions.php';

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

$fail = 0;
function check($name, $got, $want, &$fail) {
  global $warnings;
  $ok = ($got === $want) && !count($warnings);
  echo ($ok ? 'ok   ' : 'FAIL ').$name.' (got '.var_export($got, true).($warnings ? ', warnings: '.implode('; ', $warnings) : '').")\n";
  if (!$ok) $fail++;
  $warnings = array();
}

check('frame present', getSlotFrame(array('frame'=>array('FrameId'=>7))), 7, $fail);
check('frame unset', getSlotFrame(array()), 1, $fail);
check('frame false (event has no Frames rows)', getSlotFrame(array('frame'=>false)), 1, $fail);
check('frame null', getSlotFrame(array('frame'=>null)), 1, $fail);
exit($fail ? 1 : 0);
