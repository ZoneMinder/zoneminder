<?php
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

function fail_unparsed($name, $detail) {
  global $failures;
  $failures++;
  echo "FAIL - $name (unparsed: $detail)\n";
}

// We only accept the table aliases actually used by these expressions.
function known_alias($alias) {
  return (strcasecmp($alias, 'E') == 0 || strcasecmp($alias, 'Event') == 0);
}

function parse_condition($cond) {
  $cond = trim($cond);
  if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.EndDateTime\s+IS\s+NOT\s+NULL$/i', $cond, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in condition '$cond'");
    return array('type' => 'end_not_null');
  }
  if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.Length\s*>\s*0$/i', $cond, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in condition '$cond'");
    return array('type' => 'length_gt0');
  }
  return array('error' => "condition '$cond'");
}

function parse_value($val) {
  $val = trim($val);
  if (preg_match('/^NOW\(\)$/i', $val)) {
    return array('type' => 'now');
  }
  // Must be checked before the bare UNIX_TIMESTAMP(StartDateTime) form.
  if (preg_match('/^UNIX_TIMESTAMP\(\s*([A-Za-z_][A-Za-z0-9_]*)\.StartDateTime\s*\)\s*\+\s*\1\.Length$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'start_ts_plus_length');
  }
  if (preg_match('/^DATE_ADD\(\s*([A-Za-z_][A-Za-z0-9_]*)\.StartDateTime\s*,\s*INTERVAL\s+FLOOR\(\s*\1\.Length\s*\)\s+SECOND\s*\)$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'start_plus_floor_length');
  }
  if (preg_match('/^UNIX_TIMESTAMP\(\s*([A-Za-z_][A-Za-z0-9_]*)\.EndDateTime\s*\)$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'end_ts');
  }
  if (preg_match('/^UNIX_TIMESTAMP\(\s*([A-Za-z_][A-Za-z0-9_]*)\.StartDateTime\s*\)$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'start_ts');
  }
  if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.EndDateTime$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'end');
  }
  if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.StartDateTime$/i', $val, $m)) {
    if (!known_alias($m[1])) return array('error' => "alias '$m[1]' in value '$val'");
    return array('type' => 'start');
  }
  return array('error' => "value '$val'");
}

// Turn "CASE WHEN <cond> THEN <value> ... ELSE <value> END" into a
// list of parsed WHEN clauses plus a parsed ELSE value.
function parse_case($sql) {
  if (!preg_match('/CASE\s+(.*?)\s+END/is', $sql, $m)) {
    return array('error' => 'no CASE ... END');
  }
  $body = $m[1];

  preg_match_all('/WHEN\s+(.*?)\s+THEN\s+(.*?)(?=\s+WHEN\s+|\s+ELSE\s+|$)/is', $body, $sets, PREG_SET_ORDER);
  if (!$sets) return array('error' => 'no WHEN clauses');

  $clauses = array();
  foreach ($sets as $set) {
    $cond = parse_condition($set[1]);
    if (isset($cond['error'])) return $cond;
    $value = parse_value($set[2]);
    if (isset($value['error'])) return $value;
    $clauses[] = array('cond' => $cond['type'], 'value' => $value['type']);
  }

  if (!preg_match('/ELSE\s+(.*)$/is', $body, $em)) {
    return array('error' => 'no ELSE clause');
  }
  $elseValue = parse_value($em[1]);
  if (isset($elseValue['error'])) return $elseValue;

  return array('clauses' => $clauses, 'else' => $elseValue['type']);
}

function eval_value($type, $row, $now) {
  switch ($type) {
    case 'end':
    case 'end_ts':
      return $row['end'];
    case 'start':
    case 'start_ts':
      return $row['start'];
    case 'start_ts_plus_length':
      return $row['start'] + $row['length'];
    case 'start_plus_floor_length':
      return $row['start'] + (int)floor($row['length']);
    case 'now':
      return $now;
  }
  return null;
}

function eval_case($parsed, $row, $now) {
  foreach ($parsed['clauses'] as $clause) {
    $matched = false;
    if ($clause['cond'] === 'end_not_null') {
      $matched = ($row['end'] !== null);
    } elseif ($clause['cond'] === 'length_gt0') {
      $matched = ($row['length'] > 0);
    }
    if ($matched) return eval_value($clause['value'], $row, $now);
  }
  return eval_value($parsed['else'], $row, $now);
}

function run_expression($label, $sql, $rows, $now) {
  $parsed = parse_case($sql);
  if (isset($parsed['error'])) {
    fail_unparsed($label, $parsed['error']);
    return;
  }
  foreach ($rows as $rowName => $row) {
    check("$label $rowName", eval_case($parsed, $row, $now), $row['want']);
  }
}

// Datetimes are plain unix integers here, so UNIX_TIMESTAMP() is an identity.
// now is far in the future so a NOW() fallback cannot masquerade as a real end.
$now = 9999;
$rows = array(
  'closed' => array('end' => 500, 'length' => 400, 'start' => 100, 'want' => 500),
  'open' => array('end' => null, 'length' => 90, 'start' => 100, 'want' => 190),
  'orphan' => array('end' => null, 'length' => 0, 'start' => 100, 'want' => 100),
);

$eventFile = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/api/app/Model/Event.php';
$timelineFile = isset($argv[2]) ? $argv[2] : __DIR__.'/../../web/skins/classic/views/timeline.php';

$event = file_get_contents($eventFile);
preg_match_all("/'(EndTimeSecs|EndTime)'\s*=>\s*'([^']*)'/", $event, $vm, PREG_SET_ORDER);
$fields = array();
foreach ($vm as $set) {
  $fields[$set[1]] = $set[2];
}

foreach (array('EndTimeSecs', 'EndTime') as $name) {
  if (!isset($fields[$name])) {
    fail_unparsed("Event.php $name", 'virtualField not found');
    continue;
  }
  run_expression("Event.php $name", $fields[$name], $rows, $now);
}

$timeline = file_get_contents($timelineFile);
if (preg_match('/max\(\s*(CASE\b.*?\bEND)\s*\)\s*AS\s+MaxTime/is', $timeline, $tm)) {
  run_expression('timeline.php MaxTime', $tm[1], $rows, $now);
} else {
  $failures++;
  echo "FAIL - timeline.php MaxTime (no CASE expression for MaxTime)\n";
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
