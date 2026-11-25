<?php
$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');

define('ZM_WEB_XFRAME_WARN', 0);

$source = file_get_contents("$repoRoot/web/includes/functions.php");
if ($source === false) {
  fwrite(STDERR, "Could not read $repoRoot/web/includes/functions.php\n");
  exit(2);
}

function extractFunction($source, $name) {
  $start = strpos($source, "function $name");
  if ($start === false) {
    fwrite(STDERR, "Could not find function $name\n");
    exit(2);
  }
  $end = strpos($source, "\n}\n", $start);
  if ($end === false) {
    fwrite(STDERR, "Could not find end of function $name\n");
    exit(2);
  }
  return substr($source, $start, $end - $start + 3);
}

eval(extractFunction($source, 'validHtmlStr'));
eval(extractFunction($source, 'getWebSiteUrl'));

$failures = 0;
$total = 0;

function check($label, $cond) {
  global $failures, $total;
  $total++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    echo "  NOT OK - $label\n";
    $failures++;
  }
}

$html = getWebSiteUrl('id1', "http://x/x'+alert(document.domain)+'", 100, 100, 't');
check('case1: no raw single quote', strpos($html, "'") === false);
check('case1: contains &#039;', strpos($html, '&#039;') !== false);

$html = getWebSiteUrl('id2', 'http://x/" onload="alert(1)', 100, 100, 't');
check('case2: exactly 10 double quotes', substr_count($html, '"') === 10);
check('case2: no onload="', strpos($html, 'onload="') === false);

$html = getWebSiteUrl('id3', 'http://x/', 100, 100, '"><script>x</script>');
check('case3: no <script>', strpos($html, '<script>') === false);

$expected = '<object id="liveStream1" data="http://cam.local/a?b=1&amp;c=2" alt="Front" width="100%" height="auto"></object>';
$actual = getWebSiteUrl('liveStream1', 'http://cam.local/a?b=1&c=2', '100%', 'auto', 'Front');
check('case4: normal input exact match', $actual === $expected);

$html = getWebSiteUrl('id5', 'http://x/', null, null, 't');
check('case5: null width/height render empty', strpos($html, 'width="" height=""') !== false);

echo "$total tests, $failures failures\n";
exit($failures > 0 ? 1 : 0);
