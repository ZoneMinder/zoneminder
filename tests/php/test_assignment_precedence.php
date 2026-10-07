<?php
// Tests that no assignment in the web ui is cut short by "and" or "or".
//
// php's "and" and "or" bind *looser* than "=", so
//
//   $drawable = ($schedule === '') or ($parts !== null);
//
// parses as ($drawable = ($schedule === '')) or ($parts !== null): the
// right-hand clause is evaluated for its side effects and thrown away, and
// $drawable gets only the left one. "||" and "&&" bind tighter than "=" and do
// what the line looks like it does.
//
// This is a bad one to have in a view. There is no error, no warning, and
// php -l is happy; the expression is legal and simply means something else.
// The instance that prompted this test made every saved schedule render as
// raw cron instead of "Every day at 21:00", and the symptom pointed at the
// cron parser, which was working perfectly.
//
// Tokenized rather than grepped so that an "and" or "or" safely inside
// brackets -- which is most of them in this tree, in conditions and ternaries
// -- is not reported.
//
// Run: php tests/php/test_assignment_precedence.php   (from the repo root)

$root = realpath(__DIR__.'/../..');

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

/**
 * Assignments whose right-hand side has an unbracketed "and" / "or" / "xor".
 *
 * Walks forward from each "=" to the statement's end, tracking bracket depth,
 * and reports a logical keyword found at depth zero. Inside brackets the
 * keyword cannot reach past the assignment, so it is harmless there.
 *
 * @param string $src php source
 * @return array one "line $var = ... or" description per find
 */
function cut_short_assignments($src) {
  $tokens = @token_get_all($src);
  if (!$tokens) return array();

  $significant = array();
  foreach ($tokens as $token) {
    if (is_array($token) and
        in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) continue;
    $significant[] = $token;
  }

  $found = array();
  for ($i = 0; $i < count($significant); $i++) {
    // A plain assignment to a variable. Compound assignments (.=, +=) have
    // their own token and are not "=", so they are not considered; neither is
    // "==", for the same reason.
    if ($significant[$i] !== '=') continue;
    $target = isset($significant[$i - 1]) ? $significant[$i - 1] : null;
    if (!is_array($target) or $target[0] !== T_VARIABLE) continue;

    $depth = 0;
    for ($j = $i + 1; $j < count($significant); $j++) {
      $token = $significant[$j];
      if ($token === '(' or $token === '[' or $token === '{') {
        $depth++;
        continue;
      }
      if ($token === ')' or $token === ']' or $token === '}') {
        // A closing bracket at depth zero ends the enclosing block, so the
        // statement is over.
        if ($depth === 0) break;
        $depth--;
        continue;
      }
      // End of statement.
      if ($depth === 0 and ($token === ';' or $token === ',')) break;
      if ($depth !== 0) continue;
      if (is_array($token) and
          in_array($token[0], array(T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR), true)) {
        // The line is for the report; the key is what gets compared. A line
        // number is not a stable identity for a finding -- anything inserted
        // above it renumbers it, and a known-issues list keyed on one goes
        // stale the next time the file is edited, which is exactly what
        // happened to this test's first version.
        $found[] = array(
          'key' => $target[1].' = ... '.strtolower($token[1]),
          'line' => $target[2],
        );
        break;
      }
    }
  }
  return $found;
}

echo "the scan can tell the difference\n";
// Given before the tree-wide check, because a scan that matched nothing would
// pass that check on its own.
// A helper so the self-checks read as the keys they are about.
function keys_of($found) {
  $keys = array();
  foreach ($found as $f) $keys[] = $f['key'];
  return $keys;
}
check('an assignment cut short by or is caught',
      keys_of(cut_short_assignments('<?php $a = f() or g();')), array('$a = ... or'));
check('and one cut short by and',
      keys_of(cut_short_assignments('<?php $a = f() and g();')), array('$a = ... and'));
check('the same expression with || is fine',
      keys_of(cut_short_assignments('<?php $a = f() || g();')), array());
check('an or inside brackets is fine',
      keys_of(cut_short_assignments('<?php $a = ($x or $y);')), array());
check('including in a ternary condition',
      keys_of(cut_short_assignments('<?php $a = ($x or $y) ? 1 : 2;')), array());
check('and in a call\'s arguments',
      keys_of(cut_short_assignments('<?php $a = f($x or $y);')), array());
check('an or in a condition is not an assignment',
      keys_of(cut_short_assignments('<?php if ($x or $y) { $a = 1; }')), array());
check('nor is one in a while',
      keys_of(cut_short_assignments('<?php while ($x or $y) { $a = 1; }')), array());
check('a comparison is not an assignment',
      keys_of(cut_short_assignments('<?php if ($a == f() or g()) { }')), array());
check('a later statement does not leak into an earlier one',
      keys_of(cut_short_assignments('<?php $a = 1; if ($x or $y) { }')), array());
// The line that prompted all this, as it was written.
check('the reported line is caught',
      keys_of(cut_short_assignments(
          '<?php $drawable = ($action->Schedule() === \'\') or ($parts !== null);')),
      array('$drawable = ... or'));

echo "nothing new under web/ cuts an assignment short\n";

// What the scan found in the rest of the tree when this test was written.
// This is a list of open questions, not a list of things that are fine.
//
// Two of them are the deliberate idiom: "$fp = fopen(...) or error_log(...)"
// relies on the loose binding on purpose, and the variable does get the fopen
// result. They are listed because the scan cannot tell intent, and because
// writing the failure branch out as an if would let them come off the list.
//
// The other four are the same bug as the one this test was written for, and
// each changes behaviour to fix, so they are left for a decision rather than
// swept up here:
//
//   filter.php:400  $canSave = !$filter->Id() or $canEdit
//                   -- reads as "a new filter can always be saved, an
//                   existing one only with edit rights", but assigns only
//                   !Id(), so an existing filter is never savable.
//   filter.php:401  $canDelete = $filter->Id() and $canEdit
//                   -- assigns only Id(), so delete is offered for any
//                   existing filter whether or not the user may edit it.
//                   This one is access control, so it is not mine to guess at.
//   console.php:110 $show_storage_areas -- assigns only the count test, so
//                   the canEdit('System') half never applies.
//   monitor.php:1483 $stream_available = canView('Stream') and ... or ...
//                   -- assigns only canView('Stream'). The intent is also
//                   ambiguous between A && (B || C) and (A && B) || C, which
//                   is a second reason not to rewrite it unasked.
//
// Anything not on this list fails, so a new one cannot be added -- including
// the one this test was written for, which is deliberately absent.
$known = array(
  'web/includes/config.php $cfg = ... or',
  'web/skins/classic/views/_options_dnsmasq.php $cfg = ... or',
  'web/skins/classic/views/filter.php $canSave = ... or',
  'web/skins/classic/views/filter.php $canDelete = ... and',
  'web/skins/classic/views/console.php $show_storage_areas = ... and',
  'web/skins/classic/views/monitor.php $stream_available = ... and',
);

$offenders = array();
$where = array();   // key => path:line, for the report only
$scanned = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/web'));
foreach ($iterator as $file) {
  if ($file->isDir() or $file->getExtension() !== 'php') continue;
  $path = $file->getPathname();
  // web/api is cakephp, assets are third party.
  if (strpos($path, '/api/') !== false) continue;
  if (strpos($path, '/assets/') !== false) continue;
  $scanned++;
  foreach (cut_short_assignments(file_get_contents($path)) as $find) {
    $relative = substr($path, strlen($root) + 1);
    $offenders[] = $relative.' '.$find['key'];
    $where[$relative.' '.$find['key']] = $relative.':'.$find['line'];
  }
}
check('a useful number of files were scanned', $scanned > 100, true);

sort($offenders);
$expected = $known;
sort($expected);
check('nothing cuts an assignment short that was not already on the list',
      array_values(array_diff($offenders, $expected)), array());
// The other direction, so the list shrinks as they are dealt with rather than
// sitting here forever describing lines that no longer look like that.
check('and every line on the list is still there',
      array_values(array_diff($expected, $offenders)), array());

echo "the actions table's schedule reads as a sentence again\n";
// The line this was all for. Checked by name rather than by scanning, because
// its absence from the list above would pass quietly if the line were deleted
// along with the feature.
$view = file_get_contents($root.'/web/skins/classic/views/monitor.php');
check('the drawable test is a single expression',
      (bool)preg_match('/\$drawable\s*=\s*\(.*?\)\s*\|\|\s*\(.*?\);/', $view), true);
check('and does not use the loose or',
      (bool)preg_match('/\$drawable\s*=[^;]*\bor\b/', $view), false);
// What it has to decide, stated once: an unset schedule draws (the pickers get
// their defaults), a schedule cronToParts understands draws, and one it does
// not is shown as cron.
require_once($root.'/web/includes/CronTab.php');
foreach (array('' => true, '0 21 * * *' => true, '30 22 * * 1' => true,
               '*/5 * * * *' => false, 'nonsense' => false) as $spec => $want) {
  $parts = cronToParts((string)$spec);
  check('"'.$spec.'" '.($want ? 'draws' : 'shows as cron'),
        (((string)$spec === '') || ($parts !== null)), $want);
}

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
