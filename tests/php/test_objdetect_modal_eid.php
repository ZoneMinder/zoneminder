<?php
// ajax/modals/objdetect.php checked eid with validInt() but echoed the raw
// value, so eid=1"><b>x</b> passed the check and broke out of the img src.
//
// Run as: php tests/php/test_objdetect_modal_eid.php

namespace ZM {
  function Error($m) { $GLOBALS['logged'] = $m; }
}

namespace {
function validInt($v) { return is_scalar($v) ? preg_replace('/[^\-\d]/', '', $v) : ''; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function translate($s) { return $s; }

function render($eid) {
  $_REQUEST = array('eid' => $eid);
  ob_start();
  include __DIR__.'/../../web/ajax/modals/objdetect.php';
  return ob_get_clean();
}
function img_src($html) {
  return preg_match('/<img src="([^"]*)"/', $html, $m) ? $m[1] : null;
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$html = render('12');
check('a numeric eid renders the image', img_src($html), '?view=image&amp;eid=12&amp;fid=objdetect');

$html = render('1"><b>x</b>');
check('a crafted eid is not echoed', strpos($html, '<b>x</b>'), false);
check('and the src keeps only its digits', img_src($html), '?view=image&amp;eid=1&amp;fid=objdetect');

check('a non-numeric eid renders nothing', render('abc'), '');

exit($failures ? 1 : 0);
}
