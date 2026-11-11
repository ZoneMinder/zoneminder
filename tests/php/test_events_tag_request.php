<?php
// The events list takes a Tag request value, like MonitorId or StartDateTime. It was
// added as a filter term on the attribute Tag, which FilterTerm turns into E.Tag, a
// column Events does not have, so the request failed. Tags is the attribute it maps
// to the tag table, with 0 meaning no tag and -1 any tag.
//
// The term is taken from web/ajax/events.php, turned into SQL by the real Filter and
// run against the events list's joins on the real database, inside a transaction that
// is rolled back.
//
// Run: sudo -u www-data php tests/php/test_events_tag_request.php   (from the repo root)
set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');
require_once('Filter.php');
function translate($s) { return $s; }

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

$source = file_get_contents(__DIR__.'/../../web/ajax/events.php');
if (!preg_match('/if \(!empty\(\$_REQUEST\[\'Tag\'\]\)\) \{\s*\$filter->addTerm\((array\(.*?\))\);/s', $source, $m)) {
  echo "FAIL - could not find the Tag term in events.php\n";
  exit(1);
}

function tagged($tag, $mid) {
  global $m;
  $_REQUEST = array('Tag' => (string)$tag);
  $filter = new ZM\Filter();
  $filter->addTerm(eval('return '.$m[1].';'));
  $sql = 'SELECT DISTINCT E.Id FROM Events AS E
    LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId
    LEFT JOIN Tags AS T ON T.Id = ET.TagId
    WHERE E.MonitorId = ? AND ('.$filter->sql().') ORDER BY E.Id';
  try {
    return array_map('intval', dbFetchAll($sql, 'Id', array($mid)) ?: array());
  } catch (Exception $e) {
    return 'error';
  }
}

$db = zmDbConn();
$db->beginTransaction();
try {
  dbQuery("INSERT INTO Monitors (Name) VALUES ('testtagrequest')");
  $mid = (int)dbInsertId();
  $ids = array();
  for ($i = 0; $i < 3; $i++) {
    dbQuery('INSERT INTO Events (MonitorId, StateId, Name, StartDateTime) VALUES (?, 1, ?, NOW())', array($mid, 'testtagrequest '.$i));
    $ids[] = (int)dbInsertId();
  }
  dbQuery("INSERT INTO Tags (Name) VALUES ('testtagrequest')");
  $tag = (int)dbInsertId();
  dbQuery('INSERT INTO Events_Tags (TagId, EventId) VALUES (?, ?)', array($tag, $ids[1]));

  check('Tag lists the events with that tag', tagged($tag, $mid), array($ids[1]));
  check('Tag 0 lists the events without a tag', tagged(0, $mid), array($ids[0], $ids[2]));
  check('Tag -1 lists the events with any tag', tagged(-1, $mid), array($ids[1]));
} finally {
  $db->rollBack();
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
