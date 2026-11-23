<?php
// The timeline overlay must keep a usable activity area when many monitors are
// shown, and the generated JS must survive invalid UTF-8 in event data.
$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);

$ok = true;

// ---- Part 1: overlay geometry ----
$src = file_get_contents($root.'/web/skins/classic/views/timeline.php');
if ($src === false) {
  echo "FAIL cannot read timeline.php\n";
  exit(1);
}

$startMarker = '$graphHeight = $chart[\'graph\'][\'height\'];';
$endMarker = '$chart[\'eventBars\'] = array();';
$start = strpos($src, $startMarker);
$end = strpos($src, $endMarker);
if ($start === false || $end === false || $end <= $start) {
  echo "FAIL overlay markers not found\n";
  exit(1);
}
// The sliced block deliberately stops before the eventBars line, leaving the
// outer "if ( $mode == 'overlay' ) {" open; close it so the snippet parses.
$block = substr($src, $start, $end - $start)."\n}\n";

foreach (array(1, 3, 14, 15, 20, 40) as $n) {
  $mode = 'overlay';
  $monitors = array_fill(1, $n, 1);
  $chart = array(
    'graph' => array('height' => 160),
    'data' => array('y' => array('range' => 1000)),
  );

  try {
    eval($block);
  } catch (\Throwable $e) {
    echo 'FAIL overlay N='.$n.' threw '.get_class($e).': '.$e->getMessage()."\n";
    $ok = false;
    continue;
  }

  $activityHeight = isset($chart['graph']['activityHeight']) ? $chart['graph']['activityHeight'] : null;
  $eventsHeight = isset($chart['graph']['eventsHeight']) ? $chart['graph']['eventsHeight'] : null;
  $density = isset($chart['data']['y']['density']) ? $chart['data']['y']['density'] : null;

  $pass = ($activityHeight !== null && $activityHeight >= 1
        && $activityHeight >= 30
        && $density !== null && $density > 0
        && $eventsHeight !== null && ($eventsHeight + $activityHeight) == 160);

  if ($n == 3) {
    $pass = $pass
      && isset($chart['graph']['eventBarHeight']) && $chart['graph']['eventBarHeight'] == 13
      && $eventsHeight == 41;
  }

  echo ($pass ? 'ok   ' : 'FAIL ')
    .'overlay N='.$n
    .' activityHeight='.var_export($activityHeight, true)
    .' eventsHeight='.var_export($eventsHeight, true)
    .' eventBarHeight='.(isset($chart['graph']['eventBarHeight']) ? $chart['graph']['eventBarHeight'] : 'unset')
    .' density='.var_export($density, true)
    ."\n";
  if (!$pass) $ok = false;
}

// ---- Part 2: JSON encoding tolerates invalid UTF-8 ----
$jsSrc = file_get_contents($root.'/web/skins/classic/views/js/timeline.js.php');
if ($jsSrc === false) {
  echo "FAIL cannot read timeline.js.php\n";
  exit(1);
}

$pos = 0;
$calls = 0;
$jsonOk = true;
while ( ($pos = strpos($jsSrc, 'json_encode(', $pos)) !== false ) {
  $argStart = $pos + strlen('json_encode(');
  $close = strpos($jsSrc, ')', $argStart);
  if ($close === false) {
    echo "FAIL json_encode call without closing paren\n";
    $jsonOk = false;
    break;
  }
  $args = substr($jsSrc, $argStart, $close - $argStart);
  $calls++;
  if (strpos($args, 'JSON_INVALID_UTF8_SUBSTITUTE') === false) {
    echo 'FAIL json_encode at offset '.$pos.' missing JSON_INVALID_UTF8_SUBSTITUTE: '.trim($args)."\n";
    $jsonOk = false;
  }
  $pos = $close + 1;
}

if ($calls === 0) {
  echo "FAIL no json_encode calls found in timeline.js.php\n";
  $jsonOk = false;
} else if ($jsonOk) {
  echo 'ok   all '.$calls." json_encode call(s) use JSON_INVALID_UTF8_SUBSTITUTE\n";
}

$encoded = json_encode(array('Notes' => "bad\xB1utf8"), JSON_INVALID_UTF8_SUBSTITUTE);
if ($encoded === false) {
  echo "FAIL json_encode with invalid UTF-8 and JSON_INVALID_UTF8_SUBSTITUTE returned false\n";
  $jsonOk = false;
} else {
  echo "ok   json_encode with invalid UTF-8 substitutes instead of failing\n";
}

if (!$jsonOk) $ok = false;

exit($ok ? 0 : 1);
