<?php
// GET /api/tags.json now returns each tag with the number of its events the caller may
// view, as ajax/tags.php gettags shows, so a client does not need a request per tag.
// The tag list itself stays unrestricted: a tag whose events are all on hidden monitors
// is still listed, with Events 0. The merge is a pure helper so it can be tested
// without a database; index() must call it.
//
// Run as: php tests/php/test_api_tag_event_counts.php [repo root]

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : dirname(__DIR__, 2);
$controller = $root.'/web/api/app/Controller/TagsController.php';
$source = @file_get_contents($controller);
if ($source === false) {
  fwrite(STDERR, "cannot read $controller\n");
  exit(2);
}

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function extract_method($source, $name) {
  if (!preg_match('/\bfunction\s+'.$name.'\s*\(/', $source, $m, PREG_OFFSET_CAPTURE)) return null;
  $start = $m[0][1];
  $brace = strpos($source, '{', $start);
  if ($brace === false) return null;
  $depth = 0;
  for ($i = $brace; $i < strlen($source); $i++) {
    if ($source[$i] === '{') $depth++;
    else if ($source[$i] === '}' && --$depth === 0) return substr($source, $start, $i - $start + 1);
  }
  return null;
}

$helper = extract_method($source, 'tagEventCounts');
if ($helper === null) {
  echo "FAIL - TagsController has no tagEventCounts helper\n";
  exit(1);
}
$helper = preg_replace('/\bprotected\s+function\s+/', 'public function ', $helper, 1);
eval('class TagEventCountHarness { '.$helper.' }');

$h = new TagEventCountHarness();
$tags = array(
  array('Tag' => array('Id' => 1, 'Name' => 'car')),
  array('Tag' => array('Id' => 2, 'Name' => 'unused')),
  array('Tag' => array('Id' => 3, 'Name' => 'secret')),
  array('Tag' => array('Id' => 4, 'Name' => 'visible')),
);
// Visible counts per tag; tag 3's events are all on hidden monitors.
$visibleCounts = array(1 => 3, 4 => 7);

$rows = $h->tagEventCounts($tags, $visibleCounts);
$byName = array();
foreach ($rows as $row) $byName[$row['Tag']['Name']] = $row['Tag']['Events'];

check('per-tag visible counts are merged into each row', $byName,
  array('car' => 3, 'unused' => 0, 'secret' => 0, 'visible' => 7));
check('every tag is still listed', count($rows), 4);

$index = extract_method($source, 'index');
check('index() calls the helper', $index !== null && strpos($index, '$this->tagEventCounts(') !== false, true);
check('index() counts only events on viewable monitors',
  $index !== null && strpos($index, 'array_merge(array(\'Events.Id = Events_Tags.EventId\'), $monitorCondition)') !== false, true);
check('index() groups the count by tag',
  $index !== null && preg_match('/[\'"]group[\'"]\s*=>\s*array\(\s*[\'"]Tag\.Id[\'"]\s*\)/', $index) === 1, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
