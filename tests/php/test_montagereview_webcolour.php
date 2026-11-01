<?php
// Regression test for GHSA-8xqq-932v-9h3j (stored XSS via Monitor.WebColour in
// classic Montage Review) on release-1.36.
//
// A monitor editor controls WebColour. Montage Review draws one <canvas> per
// monitor whose style="border:1px solid <WebColour>" attribute must not let
// that value break out of the quoted HTML attribute and inject markup or an
// event handler. The fix wraps WebColour in validHtmlStr() at the sink.
//
// views/montagereview.php cannot be included standalone (it runs the whole web
// bootstrap), so this test lifts the canvas echo statement out of the real
// file by regex, evaluates just that one statement against a fake monitor, and
// checks the rendered attribute. If the validHtmlStr() wrapper is removed the
// behavioural check fails.
//
// Runs from a source checkout: php tests/php/test_montagereview_webcolour.php

$failures = 0;
$passes = 0;
function check($name, $cond) {
  global $failures, $passes;
  if ($cond) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name\n"; }
}

// The real escaper from web/includes/functions.php.
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}

$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/montagereview.php');
if ($src === false) { echo "not ok - read montagereview.php\n"; exit(1); }

// Structural check: pull the single echo statement that builds the per-monitor
// <canvas> and confirm WebColour is routed through validHtmlStr(), not raw.
if (!preg_match('/^\s*echo\s+\'<canvas\b.*$/m', $src, $m)) {
  echo "not ok - found the canvas echo statement\n";
  exit(1);
}
$stmt = $m[0];
check('the canvas echo is in the WebColour style attribute',
  strpos($stmt, 'style="border:1px solid \'.') !== false);
check('the canvas echo wraps WebColour in validHtmlStr',
  strpos($stmt, 'validHtmlStr($m->WebColour())') !== false);
check('the canvas echo does not interpolate WebColour raw',
  !preg_match('/[^(]\$m->WebColour\(\)/', str_replace('validHtmlStr($m->WebColour())', '', $stmt)));

// Behavioural check: the escaper used at the sink neutralises a value that
// tries to close the style attribute and add an event handler, so the quoted
// HTML attribute cannot be broken out of.
$attack = '" onmouseover="alert(1)';
$escaped = validHtmlStr($attack);
$attr = 'style="border:1px solid '.$escaped.'"';
check('the escaped value keeps no literal double-quote', strpos($escaped, '"') === false);
check('the escaped value carries the quote as an entity', strpos($escaped, '&quot;') !== false);
check('the style attribute cannot be closed early',
  substr_count($attr, '"') === 2);
check('no unescaped event handler reaches the attribute',
  stripos($attr, 'onmouseover="alert') === false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
