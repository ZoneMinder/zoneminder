<?php
// Tests that the monitor actions table's column widths land on the columns
// they were written for.
//
// The widths live in web/skins/classic/css/base/views/monitor.css and are
// addressed positionally, as :nth-child(1), (3) and (6), because the markup in
// web/skins/classic/views/monitor.php gives the cells no classes. That is the
// ordinary way to size table columns, and it is fine until a column is
// inserted or the order changes: then every pin after it silently moves one
// column to the right. Nothing errors. Enabled simply stops being 4em wide
// and whatever followed it becomes 4em wide instead.
//
// So the two files are compared. The css is read for which positions it pins,
// the view is read for what is in each position, and the pins must still be
// on the columns the css says they are.
//
// Run: php tests/php/test_monitor_actions_columns.php   (from the repo root)

$root = __DIR__.'/../..';
$view = file_get_contents($root.'/web/skins/classic/views/monitor.php');
$css = file_get_contents($root.'/web/skins/classic/css/base/views/monitor.css');

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

// The table's <thead>, as the view writes it.
if (!preg_match('/<table id="monitorActionsTable".*?<\/thead>/s', $view, $m))
  die("could not find the actions table's thead in the view\n");
$thead = $m[0];

// Each header is translate('Key'), so the keys are the column names.
preg_match_all("/<th><\?php echo translate\('([A-Za-z]+)'\) \?><\/th>/", $thead, $m);
$columns = $m[1];

echo "the view's columns are the ones the css was written against\n";
check('six columns', count($columns), 6);
// Named, so that a reordering shows up here as a diff rather than as a silent
// change of which column is narrow.
check('in this order', $columns, array(
  'Enabled',          // 1: a checkbox
  'ActionTrigger',    // 2: When
  'ActionSchedule',   // 3: Every <frequency> at <time>
  'ActionTarget',     // 4: Device
  'ActionType',       // 5: Does
  'ActionAudioFile',  // 6: Sound
));

// Every row has to have a cell per header, or the columns do not line up and
// the widths land on the wrong content from that cell onwards.
if (!preg_match('/<tr class="monitorActionRow">.*?<\/tr>/s', $view, $m))
  die("could not find an action row in the view\n");
$row = $m[0];
check('and a body row has a cell for each', preg_match_all('/<td[\s>]/', $row), count($columns));

echo "the css pins the columns it says it does\n";
// Which positions the css gives a width to, and what width.
preg_match_all(
  '/#monitorActionsTable th:nth-child\((\d+)\),\s*'.
  '#monitorActionsTable td:nth-child\(\d+\)\s*\{([^}]*)\}/s', $css, $m, PREG_SET_ORDER);
$pinned = array();
foreach ($m as $rule) {
  if (preg_match('/width:\s*([^;]+);/', $rule[2], $w))
    $pinned[(int)$rule[1]] = trim($w[1]);
}
ksort($pinned);
check('three columns are pinned', count($pinned), 3);

// The point of the exercise: each pin is on the column it was written for.
// Read out of the view by position rather than restated, so a reordering
// fails here instead of going unnoticed.
function column_at($columns, $n) {
  return isset($columns[$n - 1]) ? $columns[$n - 1] : null;
}
$pinned_columns = array();
foreach ($pinned as $n => $width) $pinned_columns[column_at($columns, $n)] = $width;
check('and they are Enabled, Schedule and Sound', array_keys($pinned_columns),
      array('Enabled', 'ActionSchedule', 'ActionAudioFile'));

echo "the widths suit what is in the columns\n";
// Not exact values -- those are a judgement and may be tuned -- but the
// ordering between them, which is the thing that would be wrong if a pin
// moved: the sentence needs more room than the checkbox or the number.
$ems = array();
foreach ($pinned_columns as $name => $width) {
  check($name.' is pinned in em', (bool)preg_match('/^([\d.]+)em$/', $width, $w), true);
  $ems[$name] = isset($w[1]) ? (float)$w[1] : 0;
}
check('Schedule is the widest of the three',
      ($ems['ActionSchedule'] > $ems['Enabled']) and
      ($ems['ActionSchedule'] > $ems['ActionAudioFile']), true);
check('and Enabled is the narrowest, being one checkbox',
      ($ems['Enabled'] < $ems['ActionAudioFile']), true);

echo "a fixed layout is what makes those widths bind\n";
// Under the default automatic layout a width on a cell is only a suggestion,
// so the pins above do nothing without this. It is the whole mechanism, and
// it is one easily-deleted line.
check('the table is laid out fixed',
      (bool)preg_match('/#monitorActionsTable\s*\{[^}]*table-layout:\s*fixed/s', $css), true);
// Bootstrap's .table supplies the 100% that a fixed layout divides up. Pinned
// in em against a table of no stated width, the columns would have nothing to
// share out.
check('and the view uses the bootstrap table class that gives it a width',
      (bool)preg_match('/<table id="monitorActionsTable" class="[^"]*\btable\b/', $view), true);
// A fixed layout will not shrink a select to fit its column, so a long
// monitor name in Device would spill into Does without this.
check('the controls are told to fit their columns',
      (bool)preg_match('/#monitorActionsTable select,\s*'.
                       '#monitorActionsTable input\s*\{[^}]*max-width:\s*100%/s', $css), true);

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
