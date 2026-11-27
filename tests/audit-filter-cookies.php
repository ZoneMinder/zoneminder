#!/usr/bin/php
<?php
# Audit two rules about the stored filter selections - the zmFilter_* and
# friends cookies shared by console, montage, montagereview and events.
#
#   1. Filter resolves nothing. It renders whatever value addTerm() was handed
#      and never reads $_COOKIE or $_REQUEST for a term. Looking a value up
#      while rendering puts the decision below the layer that knows what was
#      requested, and it silently overrides the value the caller chose.
#
#   2. A caller may only seed a term from a stored selection when the request
#      did not specify a filter of its own. Otherwise it widens or narrows the
#      filter the user asked for.
#
# Both were broken in issue #5026: an applied filter was ANDed with a date range
# left over from an earlier visit to another view, so it matched nothing and the
# events list came up empty until a manual refresh.
#
# Run from the repo root:  php tests/audit-filter-cookies.php

# Rule 1 applies here: this is the rendering layer.
$renderers = array('web/includes/Filter.php');

# Rule 2 applies here: these build terms and may consult stored selections.
$callers = array(
  'web/skins/classic/views/events.php',
  'web/skins/classic/views/montagereview.php',
);

# Cookie reads that feed a filter term. Anything else - $_COOKIE['zmWatchScale'],
# menu preferences and so on - is outside these rules.
$is_filter_cookie = '/\$term\[.cookie.\]|zmFilter_|eventsTags|eventsNotes|zmFilterArchived|_COOKIE\[.(Notes|Archived).\]|\$cookie\]/';
# Must be $use_stored used as a condition. A bare match would also hit the
# `use ($use_stored)` capture in a closure signature, which imports the flag
# without testing it - and would pass a closure that had dropped its guard.
# `if ($use_stored)` is a real guard, so it is listed explicitly.
$has_guard = '/if\s*\(\s*!?\s*\$use_stored|!\s*\$use_stored|\$use_stored\s*(\?|and\b|&&)/';

# Statements are rebuilt from lines because a value and its guard often span a
# line break; a line-based scan would report the wrapped half as though it were
# bare. Anything wider than a statement is too coarse: the enclosing block holds
# other guarded reads and would mask one that lost its own.
function statements($path) {
  $out = array();
  $buf = '';
  $start = 0;
  foreach (file($path) as $n => $line) {
    if ($buf === '') $start = $n + 1;
    $buf .= ' '.trim($line);
    if (preg_match('/[;{}]\s*$/', rtrim($line))) {
      $out[] = array($start, trim($buf));
      $buf = '';
    }
  }
  if (trim($buf) !== '') $out[] = array($start, trim($buf));
  return $out;
}

function report($file, $line, $text, $why) {
  return sprintf("  %s:%d  %s\n      %s", $file, $line,
    strlen($text) > 100 ? substr($text, 0, 100).' ...' : $text, $why);
}

$status = 0;
$checked = 0;

foreach ($renderers as $file) {
  if (!is_readable($file)) { echo "MISSING: $file\n"; $status = 1; continue; }
  $findings = array();
  foreach (statements($file) as $stmt) {
    list($line, $text) = $stmt;
    if (!preg_match('/_COOKIE|_REQUEST/', $text)) continue;
    if (!preg_match($GLOBALS['is_filter_cookie'], $text)) continue;
    $checked++;
    $findings[] = report($file, $line, $text,
      'Filter must not look this up; hand the resolved value to addTerm() instead.');
  }
  if ($findings) {
    echo "RESOLVES IN THE RENDERING LAYER - $file:\n".implode("\n", $findings)."\n\n";
    $status = 1;
  }
}

foreach ($callers as $file) {
  if (!is_readable($file)) { echo "MISSING: $file\n"; $status = 1; continue; }
  $findings = array();
  foreach (statements($file) as $stmt) {
    list($line, $text) = $stmt;
    if (strpos($text, '_COOKIE') === false) continue;
    if (!preg_match($is_filter_cookie, $text)) continue;
    $checked++;
    if (!preg_match($has_guard, $text)) {
      $findings[] = report($file, $line, $text,
        'Gate this on $use_stored, so a filter given in the request wins.');
    }
  }
  if ($findings) {
    echo "UNGUARDED STORED SELECTION - $file:\n".implode("\n", $findings)."\n\n";
    $status = 1;
  }
}

if ($status === 0) {
  echo "ok: Filter resolves nothing, and all $checked stored-selection reads are gated\n";
}
exit($status);
