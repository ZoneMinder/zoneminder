<?php
// GHSA-qm44-862r-fmq3: montage review wrote minTime / maxTime into a
// single-quoted JavaScript string in the page's nonce-bearing script without
// encoding them. The range can come from the StartDateTime terms of a filter
// in the request (filter[Query][...][val]), which montagereview.php copied
// verbatim, so a crafted link ran script for the Events viewer who opened it.
//
// Two changes close it, and this test locks both:
//   1. montagereview.php canonicalises minTime/maxTime: strtotime() then
//      date('Y-m-d H:i:s'), or null when it does not parse. Whatever the source
//      (request param, cookie, filter term) the value is afterwards a plain date
//      or null.
//   2. montagereview.js.php writes them with json_encode(... JSON_HEX_*), so even
//      a value that slipped through cannot break out of the string.
//
// Both source files pull in the whole view, so the pieces under test are lifted
// out and exercised on their own: php tests/php/test_montagereview_time_xss.php

$failures = 0;
$passes = 0;

function check($name, $cond) {
  global $failures, $passes;
  if ($cond) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name\n"; }
}

$php = file_get_contents(__DIR__.'/../../web/skins/classic/views/montagereview.php');
$js  = file_get_contents(__DIR__.'/../../web/skins/classic/views/js/montagereview.js.php');
if ($php === false or $js === false) {
  echo "not ok - could not read montagereview sources\n";
  exit(1);
}

// ---------------------------------------------------------------------------
// Part A - behavioural: run montagereview.php's real canonicalisation loop.
// ---------------------------------------------------------------------------

// Lift the `foreach (array('minTime', 'maxTime') ...) { ... }` block by brace
// matching so the test tracks the shipped code.
$start = strpos($php, "foreach (array('minTime', 'maxTime')");
check('montagereview.php has the minTime/maxTime canonicalisation loop', $start !== false);
$loop = '';
if ($start !== false) {
  $brace = strpos($php, '{', $start);
  $depth = 0;
  for ($i = $brace; $i < strlen($php); $i++) {
    if ($php[$i] === '{') $depth++;
    else if ($php[$i] === '}') { $depth--; if ($depth === 0) { $loop = substr($php, $start, $i - $start + 1); break; } }
  }
}

function canonicalise($loop, $minTime, $maxTime) {
  eval($loop);
  return array($minTime, $maxTime);
}

// A valid date (whatever its input form) survives as a plain Y-m-d H:i:s string.
list($min, $max) = canonicalise($loop, '2026-01-02 03:04:05', '2026-01-01T12:00:00+00:00');
check('a valid minTime is kept in canonical form', $min === '2026-01-02 03:04:05');
check('a valid maxTime is reduced to canonical form', $max === '2026-01-01 12:00:00');

// Filter-term XSS payloads do not parse as a time, so they become null.
$payloads = array(
  "';alert(1);//",
  '</script><script>alert(1)</script>',
  "2026-01-01'><img src=x onerror=alert(1)>",
);
foreach ($payloads as $n => $p) {
  list($min, $max) = canonicalise($loop, $p, $p);
  check("payload #$n becomes null (minTime)", $min === null);
  check("payload #$n becomes null (maxTime)", $max === null);
  // Belt and braces: whatever it becomes, it carries no string-breaking character.
  $safe = ($min === null) || (strpbrk($min, "'\"<>&\\") === false);
  check("payload #$n leaves no breakout character", $safe);
}

// ---------------------------------------------------------------------------
// Part B - source: the script emits minTime/maxTime through json_encode, not a
// raw single-quoted interpolation.
// ---------------------------------------------------------------------------

check('montagereview.js.php does not emit raw minTime',
  strpos($js, "var minTime='\$minTime'") === false);
check('montagereview.js.php does not emit raw maxTime',
  strpos($js, "var maxTime='\$maxTime'") === false);
check('montagereview.js.php json_encodes minTime',
  preg_match('/var minTime=.*json_encode\(\(string\)\$minTime/', $js) === 1);
check('montagereview.js.php json_encodes maxTime',
  preg_match('/var maxTime=.*json_encode\(\(string\)\$maxTime/', $js) === 1);
check('the json_encode uses the HTML-hex flags',
  strpos($js, 'JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT') !== false);

// ---------------------------------------------------------------------------
// Part C - behavioural: that json_encode call neutralises a breakout value even
// if one reached it.
// ---------------------------------------------------------------------------

$raw = "'; alert(document.cookie); //</script>";
$emitted = json_encode((string)$raw, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
check('json_encode escapes the single quote', strpos($emitted, "'") === false);
check('json_encode escapes angle brackets', strpos($emitted, '<') === false && strpos($emitted, '>') === false);
check('json_encode yields a quoted JS string literal',
  $emitted[0] === '"' && substr($emitted, -1) === '"');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
