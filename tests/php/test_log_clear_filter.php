<?php
// Tests the filtered "Clear Logs" path in web/ajax/log.php (issue #4727).
//
// Since the March 2026 log-view rework the Clear Logs button only deleted the
// rows the user had selected and was disabled with no selection, so clicking
// it did nothing and there was no way to clear logs that spanned many pages.
// The delete task now, when given no ids, removes every row matching the
// current view filter via the shared buildLogWhere() helper (an empty filter
// clears the whole table).
//
// This isolates buildLogWhere() by extracting it from the source (the file
// runs top-level code on include, so it cannot be required) and exercises it
// against $_REQUEST, then source-scans the delete task to confirm it uses the
// filter when no ids are supplied and still requires System edit.
//
// Run as: php tests/php/test_log_clear_filter.php

namespace ZM {
  class Logger {
    // Mirror web/includes/logger.php: level int => code string.
    public static $codes = array(
      1 => 'DBG', 0 => 'INF', -1 => 'WAR', -2 => 'ERR',
      -3 => 'FAT', -4 => 'PNC', -5 => 'AUD', -6 => 'OFF',
    );
  }
  function Error($m) {}
  function Warning($m) {}
}

namespace {
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

  $src = file_get_contents(__DIR__.'/../../web/ajax/log.php');
  if ($src === false) {
    fwrite(STDERR, "cannot read web/ajax/log.php\n");
    exit(2);
  }

  // Extract "function buildLogWhere() { ... }" by matching braces.
  $start = strpos($src, 'function buildLogWhere(');
  if ($start === false) {
    echo "FAIL - buildLogWhere() is not defined in web/ajax/log.php\n";
    exit(1);
  }
  $brace = strpos($src, '{', $start);
  $depth = 0; $end = $brace;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    else if ($src[$i] === '}') { $depth--; if ($depth === 0) { $end = $i; break; } }
  }
  $fn = substr($src, $start, $end - $start + 1);
  eval($fn);

  function run_filter(array $request) {
    $_REQUEST = $request;
    return buildLogWhere();
  }

  echo "buildLogWhere - no filter\n";
  $r = run_filter(array());
  check('empty where', $r['where'], '');
  check('no values', $r['values'], array());

  echo "\nbuildLogWhere - single level\n";
  $r = run_filter(array('level' => array('ERR')));
  check('Level IN clause', trim($r['where']), 'Level IN (?)');
  check('level code value', $r['values'], array(-2));
  check('level string remembered', $r['levels'], array('ERR'));

  echo "\nbuildLogWhere - component + server\n";
  $r = run_filter(array('Component' => array('zmc', 'zma'), 'ServerId' => '3'));
  check('component then server', $r['where'], 'Component IN (?, ?) AND ServerId = ?');
  check('values in order', $r['values'], array('zmc', 'zma', '3'));
  check('components remembered', $r['components'], array('zmc', 'zma'));

  echo "\nbuildLogWhere - date range\n";
  $r = run_filter(array('StartDateTime' => '2026-01-01 00:00:00', 'EndDateTime' => '2026-01-02 00:00:00'));
  check('two TimeKey bounds', $r['where'], 'TimeKey >= ? AND TimeKey <= ?');
  check('two timestamp values', count($r['values']), 2);
  check('start before end', $r['values'][0] < $r['values'][1], true);

  echo "\nbuildLogWhere - free search\n";
  $r = run_filter(array('search' => 'boom'));
  check('LIKE across columns, OR-joined', strpos($r['where'], ' LIKE ?) OR (') === false && strpos($r['where'], 'LIKE ?') !== false, true);
  check('each value is wildcarded search', $r['values'][0], '%boom%');

  echo "\nbuildLogWhere - unknown level ignored\n";
  $r = run_filter(array('level' => array('NOPE')));
  check('no clause for bogus level', $r['where'], '');

  echo "\ndelete task source\n";
  // The delete task must: delete by ids when present, else clear by filter.
  $del = substr($src, strpos($src, "== 'delete'"));
  $del = substr($del, 0, strpos($del, '} else {') !== false ? 4000 : 4000);
  check('delete still requires System edit',
    (bool)preg_match('/==\s*\'delete\'.*?canEdit\(\s*\'System\'\s*\)/s', $src), true);
  check('delete by ids path kept',
    (bool)preg_match('/!empty\(\$_REQUEST\[\'ids\'\]\).*?DELETE FROM Logs WHERE Id IN/s', $src), true);
  check('no-ids path calls buildLogWhere',
    (bool)preg_match('/else\s*\{[^}]*buildLogWhere\(\)/s', $src), true);
  check('no-ids path issues DELETE FROM Logs',
    (bool)preg_match('/buildLogWhere\(\).*?DELETE FROM Logs/s', $src), true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
