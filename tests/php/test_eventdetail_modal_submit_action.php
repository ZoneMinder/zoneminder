<?php
//
// Test: pressing Enter in the event detail modal must submit action=eventdetail
//
// The modal form carries a hidden <input type="hidden" name="action"
// value="eventdetail"/>. If the Save button also had name="action"
// value="save", a native submit (e.g. Enter in a text field) posts both
// entries, and PHP keeps the LAST one ('save'). web/includes/actions/
// eventdetail.php only acts when $action == 'eventdetail', so nothing was
// saved. The Save button must therefore have no name/value.
//
// This is a source-level test: the modal is parsed as text, no DB, no web
// server, no PHP rendering.
//
// Usage: php test_eventdetail_modal_submit_action.php [zoneminder-root]
//

$root = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : __DIR__ . '/../..';

$failures = 0;
function check($cond, $message) {
  global $failures;
  echo ($cond ? 'ok' : 'FAIL') . ' - ' . $message . "\n";
  if (!$cond) $failures++;
}

function extract_attr($tag, $name) {
  // Extract a double- or single-quoted attribute value from a tag string.
  if (preg_match('/\b' . $name . '\s*=\s*"([^"]*)"/i', $tag, $m))
    return $m[1];
  if (preg_match("/\b" . $name . "\s*=\s*'([^']*)'/i", $tag, $m))
    return $m[1];
  return null;
}

$modal_path = $root . '/web/ajax/modals/eventdetail.php';
if (!is_file($modal_path)) {
  echo "FAIL - modal file not found: $modal_path\n";
  exit(1);
}
$modal = file_get_contents($modal_path);
if ($modal === false) {
  echo "FAIL - could not read: $modal_path\n";
  exit(1);
}

//
// 1. Collect every element that would be submitted with name="action":
//    hidden inputs and submit buttons carrying name="action".
//

// Hidden inputs with name="action". The form tag's action="..." attribute
// cannot match because we anchor on <input.
$hidden_values = array();
if (preg_match_all('/<input\b[^>]*>/i', $modal, $input_tags)) {
  foreach ($input_tags[0] as $tag) {
    if (strcasecmp((string)extract_attr($tag, 'name'), 'action') === 0
        and strcasecmp((string)extract_attr($tag, 'type'), 'hidden') === 0) {
      $hidden_values[] = (string)extract_attr($tag, 'value');
    }
  }
}
check(count($hidden_values) >= 1,
  'modal has at least one hidden input with name="action" (found ' . count($hidden_values) . ')');

// All <button> tags, indexed for later use.
$buttons = array();
if (preg_match_all('/<button\b[^>]*>/i', $modal, $button_tags)) {
  foreach ($button_tags[0] as $tag) {
    $buttons[] = array(
      'tag' => $tag,
      'id' => extract_attr($tag, 'id'),
      'type' => extract_attr($tag, 'type'),
      'name' => extract_attr($tag, 'name'),
      'value' => extract_attr($tag, 'value'),
    );
  }
}

// Submit buttons with name="action" (these would be submitted when they are
// the submitter).
$submit_action_buttons = array();
foreach ($buttons as $btn) {
  if (strcasecmp((string)$btn['name'], 'action') === 0
      and strcasecmp((string)$btn['type'], 'submit') === 0) {
    $submit_action_buttons[] = $btn;
  }
}
check(count($submit_action_buttons) === 0,
  'no submit button carries name="action" (found ' . count($submit_action_buttons) . ')');

//
// 2. Simulate a native form submit with the Save button as the submitter
//    (what happens when the user presses Enter in a text field). The
//    submitted action entries are the hidden input value(s) in document
//    order, followed by the submitter's value if it has name="action".
//    PHP keeps the LAST entry.
//
$submitted = $hidden_values;
$save_btn = null;
foreach ($buttons as $btn) {
  if ($btn['id'] === 'eventDetailSaveBtn') {
    $save_btn = $btn;
    break;
  }
}
if ($save_btn !== null
    and strcasecmp((string)$save_btn['name'], 'action') === 0
    and strcasecmp((string)$save_btn['type'], 'submit') === 0) {
  $submitted[] = (string)$save_btn['value'];
}
$last_action = count($submitted) ? $submitted[count($submitted) - 1] : null;
check($last_action === 'eventdetail',
  "last submitted action value is 'eventdetail' (got " .
  var_export($last_action, true) . ', sequence: ' .
  (count($submitted) ? implode(',', array_map('strval', $submitted)) : '(empty)') . ')');

//
// 3. web/includes/actions/eventdetail.php must act on $action == 'eventdetail'.
//    Extract the compared literal from the source.
//
$actions_path = $root . '/web/includes/actions/eventdetail.php';
if (!is_file($actions_path)) {
  echo "FAIL - actions file not found: $actions_path\n";
  exit(1);
}
$actions_src = file_get_contents($actions_path);
if ($actions_src === false) {
  echo "FAIL - could not read: $actions_path\n";
  exit(1);
}
$actions_checks = 'eventdetail';
if (preg_match('/\$action\s*={2,3}\s*[\'"]([^\'"]*)[\'"]/', $actions_src, $am)) {
  $actions_checks = $am[1];
}
check($actions_checks === 'eventdetail',
  "actions/eventdetail.php compares \$action against 'eventdetail' (got " .
  var_export($actions_checks, true) . ')');

//
// 4. The Save button must still exist and be a submit button.
//
check($save_btn !== null, 'button with id="eventDetailSaveBtn" exists');
check($save_btn !== null and strcasecmp((string)$save_btn['type'], 'submit') === 0,
  'eventDetailSaveBtn is type="submit" (got ' .
  var_export($save_btn ? $save_btn['type'] : null, true) . ')');

if ($failures) {
  echo $failures . ' check(s) failed' . "\n";
  exit(1);
}
echo "all checks passed\n";
exit(0);
?>
