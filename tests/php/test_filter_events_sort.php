<?php
// Filter::Events() must turn the filter's sort field into a column of its
// Events/Monitors/Tags query. The filter editor offers MonitorName and Tags,
// which are not Events columns, so passing them through unchanged made the
// query fail ("Unknown column 'MonitorName' in 'ORDER BY'") and the report
// view showed no events.
// Run: php tests/php/test_filter_events_sort.php

$src = file_get_contents(__DIR__.'/../../web/includes/Filter.php');

function extract_method($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  if ($sig === false) return null;
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($src, $sig, $i - $sig + 1);
    }
  }
  return null;
}

$fail = 0;
function check($label, $ok) {
  global $fail;
  echo ($ok ? '  ok ' : '  FAIL ').$label."\n";
  if (!$ok) $fail++;
}

$method = extract_method($src, 'events_sort_sql');
check('Filter has events_sort_sql()', $method !== null);
if ($method === null) exit(1);
eval(preg_replace('/^function events_sort_sql\(/', 'function events_sort_sql(', $method));

check('MonitorName sorts by the monitor name', events_sort_sql('MonitorName') === 'M.Name');
check('Tags sorts by the event\'s tag names', strpos(events_sort_sql('Tags'), 'GROUP_CONCAT') !== false);
check('Name is the event name, not ambiguous', events_sort_sql('Name') === 'E.Name');
check('plain columns are qualified', events_sort_sql('StartDateTime') === 'E.StartDateTime');
check('NULLs-last form keeps its shape',
  events_sort_sql('EndDateTime IS NOT NULL, EndDateTime') === 'E.EndDateTime IS NOT NULL, E.EndDateTime');
check('no sort field gives no sort', events_sort_sql('') === '');

exit($fail ? 1 : 0);
