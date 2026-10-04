<?php
//
// ZoneMinder crontab helpers
// Copyright (C) 2026 ZoneMinder LLC
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
//
// Scheduled monitor actions are run by cron rather than by a daemon of our
// own: the web user owns a crontab it can rewrite without privilege, and cron
// has already solved running a thing at a time.
//
// Two pieces live here because they are the ones that can lose data or run
// the wrong thing, and both are pure so they can be tested on their own:
// validating a schedule before it is ever written, and splicing our block into
// a crontab without disturbing the lines around it.
//

// The markers delimiting the block ZoneMinder owns. Everything between them is
// rewritten wholesale; everything outside is somebody else's and is preserved
// byte for byte.
define('ZM_CRONTAB_BEGIN', '# BEGIN ZoneMinder scheduled actions - do not edit, managed by ZoneMinder');
define('ZM_CRONTAB_END', '# END ZoneMinder scheduled actions');

// Is $spec a cron schedule we are willing to write?
//
// Deliberately strict. This string is placed in a crontab, so anything that
// could end a field and start something else has to be refused here rather
// than relied on to be harmless later: a newline would let a schedule append
// an arbitrary cron line, and a '%' is where cron stops the command and starts
// feeding it stdin.
//
// Accepts the five standard fields with the usual *, lists, ranges and steps,
// and nothing else. Named days and months are not accepted because they vary
// between cron implementations.
function cronScheduleIsValid($spec) {
  if (!is_string($spec)) return false;
  // No newline, carriage return or percent may reach a crontab line.
  if (preg_match('/[\r\n%]/', $spec)) return false;

  $fields = preg_split('/\s+/', trim($spec), -1, PREG_SPLIT_NO_EMPTY);
  if (count($fields) != 5) return false;

  // minute, hour, day of month, month, day of week
  $ranges = array(array(0, 59), array(0, 23), array(1, 31), array(1, 12), array(0, 7));

  foreach ($fields as $i => $field) {
    list($lo, $hi) = $ranges[$i];
    foreach (explode(',', $field) as $item) {
      if ($item === '') return false;

      // A step may follow either a range or a star: */5, 0-30/10.
      $step = null;
      if (strpos($item, '/') !== false) {
        $parts = explode('/', $item);
        if (count($parts) != 2) return false;
        $item = $parts[0];
        $step = $parts[1];
        if (!preg_match('/^\d+$/', $step) or (intval($step) < 1)) return false;
      }

      if ($item === '*') continue;

      if (strpos($item, '-') !== false) {
        $parts = explode('-', $item);
        if (count($parts) != 2) return false;
        if (!preg_match('/^\d+$/', $parts[0]) or !preg_match('/^\d+$/', $parts[1])) return false;
        $from = intval($parts[0]);
        $to = intval($parts[1]);
        if (($from < $lo) or ($to > $hi) or ($from > $to)) return false;
        continue;
      }

      if (!preg_match('/^\d+$/', $item)) return false;
      $value = intval($item);
      if (($value < $lo) or ($value > $hi)) return false;
    }
  }
  return true;
}

// Replace ZoneMinder's block in $existing with $lines, leaving every other
// line exactly as it was.
//
// $lines is the body of the block, without the markers. An empty $lines
// removes the block entirely rather than leaving a pair of markers behind.
//
// The crontab is the web user's own and may well hold entries that have
// nothing to do with us, so this never rewrites the whole file. A crontab with
// no block yet gains one at the end; one with a block has it replaced in
// place, so the surrounding entries keep their order.
function cronSpliceBlock($existing, $lines) {
  $existing = is_string($existing) ? $existing : '';
  // Normalise to \n for splitting; crontabs are line oriented.
  $existing = str_replace("\r\n", "\n", $existing);
  $before = array();
  $after = array();
  $seen_begin = false;
  $seen_end = false;

  foreach (explode("\n", $existing) as $line) {
    if (!$seen_begin and (trim($line) === ZM_CRONTAB_BEGIN)) {
      $seen_begin = true;
      continue;
    }
    if ($seen_begin and !$seen_end) {
      // Inside our block: dropped, whatever it says.
      if (trim($line) === ZM_CRONTAB_END) $seen_end = true;
      continue;
    }
    if ($seen_begin) {
      $after[] = $line;
    } else {
      $before[] = $line;
    }
  }

  // A begin marker with no end means a previous write was interrupted. The
  // rest of the file was then swallowed as block body above, which would throw
  // away the user's own entries, so treat the file as untouched and append
  // instead. Losing our own stale block is the safe failure here.
  if ($seen_begin and !$seen_end) {
    $before = explode("\n", $existing);
    $after = array();
    // Strip the orphaned marker so it cannot accumulate.
    $before = array_values(array_filter($before, function($l) {
      return trim($l) !== ZM_CRONTAB_BEGIN;
    }));
  }

  // Trim blank lines at the seam so repeated writes do not grow the file.
  while (count($before) and (trim(end($before)) === '')) array_pop($before);
  while (count($after) and (trim($after[0]) === '')) array_shift($after);

  $out = $before;
  if (count($lines)) {
    if (count($out)) $out[] = '';
    $out[] = ZM_CRONTAB_BEGIN;
    foreach ($lines as $line) $out[] = $line;
    $out[] = ZM_CRONTAB_END;
  }
  foreach ($after as $line) $out[] = $line;

  // A crontab must end with a newline or the last entry is ignored by some
  // cron implementations.
  while (count($out) and (trim(end($out)) === '')) array_pop($out);
  if (!count($out)) return '';
  return implode("\n", $out)."\n";
}

// --- Installing the block --------------------------------------------------
//
// Separated from the two functions above so those stay pure and testable:
// everything below talks to the crontab command and the database.

// The web user's current crontab, or '' when it has none.
function cronRead() {
  $lines = array();
  $status = 0;
  // "no crontab for ..." on stderr with a non-zero status is the normal answer
  // for a user who has none, not a failure.
  exec('crontab -l 2>/dev/null', $lines, $status);
  return implode("\n", $lines);
}

// Replace the web user's crontab with $content. Returns true on success.
//
// Written through a pipe rather than a shell redirect so the content is never
// subject to quoting, and so an empty crontab can be installed without the
// shell turning it into something else.
function cronWrite($content) {
  $descriptors = array(
    0 => array('pipe', 'r'),
    1 => array('pipe', 'w'),
    2 => array('pipe', 'w'),
  );
  $pipes = array();
  $process = @proc_open('crontab -', $descriptors, $pipes);
  if (!is_resource($process)) {
    Error('Unable to run crontab to install scheduled actions');
    return false;
  }
  fwrite($pipes[0], $content);
  fclose($pipes[0]);
  $out = stream_get_contents($pipes[1]);
  $err = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $status = proc_close($process);

  if ($status !== 0) {
    Error('crontab refused the scheduled actions (status '.$status.'): '.trim($err.' '.$out));
    return false;
  }
  return true;
}

// Rebuild the ZoneMinder block from the scheduled actions in the database.
//
// Called whenever an action is saved or deleted, so the crontab is a
// projection of the table rather than a second place to keep the truth. The
// whole block is regenerated each time, which is why cronSpliceBlock has to
// leave everything around it alone.
//
// Returns an array of the lines installed, or false if the crontab could not
// be written.
function cronRebuildScheduledActions() {
  require_once('MonitorAction.php');

  $lines = array();
  $skipped_remote = 0;

  foreach (ZM\MonitorAction::find(array('TriggerOn'=>'Schedule', 'Enabled'=>1),
                                  array('order'=>'Id')) as $action) {
    $line = $action->cronLine();
    if (!$line) continue;

    // cron runs zmcontrol.pl here, on this machine, so an action driving a
    // monitor that belongs to another server would reach nothing. The web ui
    // proxies such a command through the API instead; cron has no such route,
    // so these are left out and counted rather than written and silently
    // ineffective.
    $target = $action->TargetMonitor();
    if ($target and defined('ZM_SERVER_ID') and ZM_SERVER_ID
        and $target->ServerId() and ($target->ServerId() != ZM_SERVER_ID)) {
      $skipped_remote++;
      continue;
    }
    $lines[] = $line;
  }

  if ($skipped_remote) {
    Warning($skipped_remote.' scheduled action(s) target a monitor on another server '.
            'and were not written to this server\'s crontab');
  }

  if (!cronWrite(cronSpliceBlock(cronRead(), $lines))) return false;

  Debug('Installed '.count($lines).' scheduled action(s) into the crontab');
  return $lines;
}
