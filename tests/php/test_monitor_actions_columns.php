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
check('four columns are pinned', count($pinned), 4);

// The point of the exercise: each pin is on the column it was written for.
// Read out of the view by position rather than restated, so a reordering
// fails here instead of going unnoticed.
function column_at($columns, $n) {
  return isset($columns[$n - 1]) ? $columns[$n - 1] : null;
}
$pinned_columns = array();
foreach ($pinned as $n => $width) $pinned_columns[column_at($columns, $n)] = $width;
check('and they are Enabled, When, Schedule and Sound', array_keys($pinned_columns),
      array('Enabled', 'ActionTrigger', 'ActionSchedule', 'ActionAudioFile'));
// Device is left loose on purpose: monitor names are the only contents here
// of unpredictable length, so that is the column that should absorb whatever
// the pinned ones do not take.
check('and Device is not among them',
      in_array('ActionTarget', array_keys($pinned_columns), true), false);

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
// The other two are the narrow ones. No ordering is asserted between them:
// under a fixed layout a column has to fit its header, which skin.css renders
// uppercase, and "ENABLED" is a wider word than "SOUND" whatever the cells
// below them hold. An earlier version of this test had Enabled as the
// narrowest "being one checkbox", which is the reasoning that got it set too
// narrow to clear its own header in the first place.
check('and the other two are narrow by comparison',
      ($ems['Enabled'] < $ems['ActionSchedule'] / 2) and
      ($ems['ActionAudioFile'] < $ems['ActionSchedule'] / 2), true);
// A column narrower than its header runs its text into the next column, which
// is how Enabled came to sit against When. The estimate below is crude on
// purpose: it is here to catch a width chosen from what the cells hold while
// forgetting the header above them, not to predict text metrics.
//
// Calibrated so that the 4em Enabled started out with fails it and the 5.5em
// it has now passes. The header text is taken from the language file rather
// than written out here, so a translation long enough to break the layout
// fails this too -- under a fixed layout it really would break it.
$SLANG = array();
require_once($root.'/web/lang/en_gb.php');
// skin.css renders every th uppercase at 0.8em, so that is what has to fit,
// along with .table-sm's 0.3rem of padding on each side.
$th_scale = 0.8;
$per_char = 0.65;  // an average uppercase advance, in the header's own em
$padding = 0.6;
foreach (array_keys($pinned_columns) as $name) {
  $text = isset($SLANG[$name]) ? $SLANG[$name] : $name;
  // strlen rather than mb_strlen: this php has no mbstring, and counting
  // bytes only over-counts a multibyte translation, which errs towards
  // demanding more width rather than less.
  $needed = (strlen($text) * $per_char * $th_scale) + $padding;
  check($name.' ('.strtoupper($text).') is wider than its header needs',
        $ems[$name] > $needed, true);
}

echo "the two select columns fit their longest option\n";
// What actually sets these two widths is not the header but the widest
// <option> in the select, since a select is as wide as its longest choice and
// a fixed layout will not grow the column to suit. That is the measurement
// these were sized from, so it is the one worth holding on to: a label
// reworded longer would start truncating with nothing to say it had.
//
// The option keys are read out of the view and the text out of the language
// file, so neither is restated here.
function widest_option($view, $array_name, $SLANG) {
  if (!preg_match('/\$'.$array_name.' = array\((.*?)\);/s', $view, $m)) return null;
  preg_match_all("/translate\('([A-Za-z]+)'\)/", $m[1], $keys);
  $widest = '';
  foreach ($keys[1] as $key) {
    $text = isset($SLANG[$key]) ? $SLANG[$key] : $key;
    if (strlen($text) > strlen($widest)) $widest = $text;
  }
  return ($widest === '') ? null : $widest;
}

// A select's chrome -- its padding and its arrow -- on top of the text. Crude,
// like the header estimate, and for the same reason.
$select_chrome = 1.8;
$per_char_lower = 0.5;

$trigger = widest_option($view, 'trigger_options', $SLANG);
check('the When options were found', $trigger, 'Manually triggered');
check('and When fits the longest of them',
      $ems['ActionTrigger'] > (strlen($trigger) * $per_char_lower) + $select_chrome, true);

$frequency = widest_option($view, 'frequency_options', $SLANG);
check('the frequency options were found', $frequency, 'weekend day (Sat & Sun)');
// Schedule holds that select plus "Every", "at" and a time input, all on one
// line, so the select is only part of what has to fit.
$every = isset($SLANG['ScheduleEvery']) ? $SLANG['ScheduleEvery'] : 'ScheduleEvery';
$at = isset($SLANG['ScheduleAt']) ? $SLANG['ScheduleAt'] : 'ScheduleAt';
$time_input = 7;
// The four controls sit on separate source lines, so there is a collapsed
// space between each pair, and the cell has .table-sm's padding either side.
// Both are easy to leave out of a sum like this and together they come to
// most of an em and a half -- which is about what the difference was between
// Schedule looking right and looking squeezed.
$inline_gaps = 3 * 0.25;
$cell_padding = 0.6;
$schedule_needs = (strlen($frequency) * $per_char_lower) + $select_chrome +
                  (strlen($every) * $per_char_lower) + (strlen($at) * $per_char_lower) +
                  $time_input + $inline_gaps + $cell_padding;
check('and Schedule fits the whole sentence',
      $ems['ActionSchedule'] > $schedule_needs, true);
// Calibration, not a property of the css: the 24em Schedule was set to before
// any of this was measured has to come out short, or the estimate above is
// too generous to have caught what was actually reported as a squeeze.
check('and the estimate is tight enough to fail the 24em that was reported',
      24 > $schedule_needs, false);

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

echo "the table gets the full width to divide up\n";
// The widths above divide whatever the table is given, so they are only half
// the story. ul.form indents every row by 300px to make a gutter for its
// label, and pulls the label back into that gutter with an equal negative
// margin. The actions rows have no label, so the table started 300px in with
// the space beside it empty -- and the columns were dividing a table 300px
// narrower than the page.
//
// A class cancels the indent for those rows. Which class is read out of the
// css rather than named here, so the two cannot drift apart silently: if they
// did, the indent would come back and nothing would error.
check('ul.form indents its rows for a label',
      (bool)preg_match('/ul\.form\s*>\s*li\s*\{[^}]*padding-left:\s*300px/s', $css), true);
check('and pulls the label back into that gutter',
      (bool)preg_match('/ul\.form\s*>\s*li\s*>\s*label:first-child\s*\{[^}]*margin-left:\s*-300px/s',
                       $css), true);

preg_match('/ul\.form\s*>\s*li\.([\w-]+)\s*\{[^}]*padding-left:\s*0/s', $css, $m);
$no_label_class = isset($m[1]) ? $m[1] : null;
check('a class cancels the indent', $no_label_class !== null, true);

if ($no_label_class !== null) {
  // The row holding the table must carry it, or the table is still indented.
  check('and the table\'s row carries it',
        (bool)preg_match('/<li class="[^"]*\b'.preg_quote($no_label_class, '/').'\b[^"]*">\s*'.
                         '<table id="monitorActionsTable"/s', $view), true);

  // The help text above and below the table reads as a caption for it, so it
  // has to start where the table starts rather than at the old indent.
  $rows = preg_match_all('/<li class="[^"]*\b'.preg_quote($no_label_class, '/').'\b/', $view);
  check('as do the other label-less rows of the tab', $rows >= 4, true);

  // Every row the class is on must genuinely have no label, otherwise the
  // label loses the gutter it is pulled into and hangs off the left edge.
  preg_match_all('/<li class="[^"]*\b'.preg_quote($no_label_class, '/').
                 '\b[^"]*">(.*?)<\/li>/s', $view, $m);
  $with_label = array();
  foreach ($m[1] as $body) {
    if (preg_match('/<label/', $body)) $with_label[] = trim(substr($body, 0, 60));
  }
  check('and none of them has one', $with_label, array());
}

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
