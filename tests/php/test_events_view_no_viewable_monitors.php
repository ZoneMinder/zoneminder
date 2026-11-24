<?php
// A user with Events view but no viewable monitors used to get "M.Id in ()" in
// web/skins/classic/views/events.php, which MariaDB rejects as a syntax error.
// The SQL-building block is cut out of the view and run with stub users so the
// check is behavioural: an empty viewable list must match nothing (in (0)),
// while a populated list and the unguarded branch stay unchanged.
//
// Run as: php tests/php/test_events_view_no_viewable_monitors.php [path-to-events.php]
// (from the repo root). The optional argument lets the same test run against the
// original file, e.g. git show HEAD:web/skins/classic/views/events.php > /tmp/events_orig.php

$path = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views/events.php';
$failures = 0;

function check($name, $ok, $detail = '') {
  global $failures;
  if ($ok) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name".($detail !== '' ? " ($detail)" : '')."\n";
  }
}

function has_empty_in($sql) {
  return preg_match('/\bin\s*\(\s*\)/i', $sql) === 1;
}

// Returns the SQL-building block: from the $eventsSql assignment through the
// closing brace of the if/else that follows it.
function events_block($path) {
  $src = file_get_contents($path);
  if ($src === false) return null;
  $needle = "\$eventsSql = 'SELECT E.*,M.Name";
  $start = strpos($src, $needle);
  if ($start === false) return null;
  $open = strpos($src, '{', $start + strlen($needle));
  if ($open === false) return null;
  $depth = 0;
  for ($i = $open, $len = strlen($src); $i < $len; $i++) {
    if ($src[$i] === '{') {
      $depth++;
    } else if ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) {
        // Keep going through an else/elseif that follows, up to its own close.
        if (strncmp(ltrim(substr($src, $i + 1)), 'else', 4) === 0) continue;
        return substr($src, $start, $i - $start + 1);
      }
    }
  }
  return null;
}

function run_block($block, $user) {
  $fn = function($block, $user) {
    eval($block);
    return get_defined_vars();
  };
  return $fn($block, $user);
}

class StubUser {
  private $viewable;
  private $unviewable;
  public function __construct(array $viewable, array $unviewable) {
    $this->viewable = $viewable;
    $this->unviewable = $unviewable;
  }
  public function viewableMonitorIds() { return $this->viewable; }
  public function unviewableMonitorIds() { return $this->unviewable; }
}

$block = events_block($path);
check('events.php SQL block found', $block !== null, $path);

if ($block !== null) {
  // (a) no viewable monitors: the filter must still be valid SQL matching nothing.
  $vars = run_block($block, new StubUser(array(), array(3)));
  $sql = isset($vars['eventsSql']) ? $vars['eventsSql'] : '';
  check('empty viewable list has no empty in ()', !has_empty_in($sql), $sql);
  check('empty viewable list contains in (0)', stripos($sql, 'in (0)') !== false, $sql);

  // (b) viewable monitors: the list is unchanged.
  $vars = run_block($block, new StubUser(array(1, 2), array(3)));
  $sql = isset($vars['eventsSql']) ? $vars['eventsSql'] : '';
  check('viewable list renders in (1,2)', strpos($sql, 'in (1,2)') !== false, $sql);

  // (c) nothing unviewable: the unfiltered branch is unchanged.
  $vars = run_block($block, new StubUser(array(1, 2), array()));
  $sql = isset($vars['eventsSql']) ? $vars['eventsSql'] : '';
  check('no unviewable monitors ends with 1', substr(rtrim($sql), -1) === '1' && substr($sql, -2) === ' 1', $sql);
}

echo "\n".($failures ? "$failures FAILED" : 'all passed')."\n";
exit($failures ? 1 : 0);
