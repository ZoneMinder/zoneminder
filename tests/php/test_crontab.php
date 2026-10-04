<?php
// Tests the two pure pieces of web/includes/CronTab.php.
//
// Scheduled monitor actions are run by cron, so ZoneMinder rewrites the web
// user's crontab. That crontab is not ours: it may hold entries an admin put
// there, and clobbering them would be a quiet, permanent loss. And the
// schedule string comes from a form and ends up in a crontab line, where a
// newline would let it append an entry of its own choosing.
//
// Both risks live in pure functions so they can be checked here rather than
// discovered on somebody's install.
//
// Run: php tests/php/test_crontab.php   (from the repo root)

require_once(__DIR__.'/../../web/includes/CronTab.php');

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

echo "schedules we accept\n";
check('every minute', cronScheduleIsValid('* * * * *'), true);
check('a specific time of day', cronScheduleIsValid('30 22 * * *'), true);
check('a list of hours', cronScheduleIsValid('0 8,12,18 * * *'), true);
check('a range of days', cronScheduleIsValid('0 9 * * 1-5'), true);
check('a step', cronScheduleIsValid('*/15 * * * *'), true);
check('a step over a range', cronScheduleIsValid('0-30/10 * * * *'), true);
check('sunday as 0 and as 7', cronScheduleIsValid('0 0 * * 0'), true);
check('sunday as 7', cronScheduleIsValid('0 0 * * 7'), true);
check('the last day of a long month', cronScheduleIsValid('0 0 31 12 *'), true);
check('extra whitespace between fields', cronScheduleIsValid('0   22  *  *  *'), true);

echo "schedules we refuse as out of range\n";
check('minute 60', cronScheduleIsValid('60 * * * *'), false);
check('hour 24', cronScheduleIsValid('0 24 * * *'), false);
check('day of month 0', cronScheduleIsValid('0 0 0 * *'), false);
check('month 13', cronScheduleIsValid('0 0 1 13 *'), false);
check('day of week 8', cronScheduleIsValid('0 0 * * 8'), false);
check('a backwards range', cronScheduleIsValid('0 22-8 * * *'), false);
check('a zero step', cronScheduleIsValid('*/0 * * * *'), false);

echo "schedules we refuse as malformed\n";
check('too few fields', cronScheduleIsValid('30 22 * *'), false);
check('too many fields', cronScheduleIsValid('30 22 * * * *'), false);
check('empty', cronScheduleIsValid(''), false);
check('not a string', cronScheduleIsValid(null), false);
check('a bare word', cronScheduleIsValid('hourly'), false);
// Named days differ between cron implementations, so they are not accepted
// rather than written and hoped for.
check('named day of week', cronScheduleIsValid('0 0 * * MON'), false);
check('an empty list item', cronScheduleIsValid('0,, * * * *'), false);
check('a range with three parts', cronScheduleIsValid('1-2-3 * * * *'), false);

echo "schedules we refuse because of what they could do to the crontab\n";
// The whole reason this function is strict. A schedule is written verbatim
// into a crontab line.
check('a newline could append an entry of its own',
  cronScheduleIsValid("* * * * *\n0 0 * * * rm -rf /"), false);
check('a carriage return likewise', cronScheduleIsValid("* * * * *\r0 0 * * * id"), false);
check('a percent is where cron ends the command', cronScheduleIsValid('* * * * %'), false);
check('a shell metacharacter', cronScheduleIsValid('* * * * *; id'), false);

echo "splicing our block into somebody else's crontab\n";

$theirs = "# the admin's own entries\n".
          "0 3 * * * /usr/local/bin/backup.sh\n".
          "@reboot /usr/local/bin/warmup.sh\n";

$ours = array('30 22 * * * /usr/bin/zmcontrol.pl --id 24 --command lightOff');

$written = cronSpliceBlock($theirs, $ours);
check('their entries survive',
  (strpos($written, '/usr/local/bin/backup.sh') !== false)
  and (strpos($written, '/usr/local/bin/warmup.sh') !== false), true);
check('ours is added', strpos($written, 'lightOff') !== false, true);
check('the block is marked at both ends',
  (substr_count($written, ZM_CRONTAB_BEGIN) === 1)
  and (substr_count($written, ZM_CRONTAB_END) === 1), true);

echo "rewriting is idempotent\n";
// Saving an action repeatedly must not grow the file or duplicate the block.
$once = cronSpliceBlock($theirs, $ours);
$twice = cronSpliceBlock($once, $ours);
$thrice = cronSpliceBlock($twice, $ours);
check('writing the same block twice changes nothing', $twice, $once);
check('and a third time still changes nothing', $thrice, $once);

echo "replacing a block\n";
$changed = cronSpliceBlock($once, array('0 6 * * * /usr/bin/zmcontrol.pl --id 24 --command lightOn'));
check('the old line is gone', strpos($changed, 'lightOff'), false);
check('the new line is there', strpos($changed, 'lightOn') !== false, true);
check('their entries still survive',
  strpos($changed, '/usr/local/bin/backup.sh') !== false, true);
check('still exactly one block', substr_count($changed, ZM_CRONTAB_BEGIN), 1);

echo "removing every schedule\n";
$emptied = cronSpliceBlock($once, array());
check('our block is gone entirely', strpos($emptied, ZM_CRONTAB_BEGIN), false);
check('no orphaned end marker', strpos($emptied, ZM_CRONTAB_END), false);
check('their entries are untouched', $emptied, $theirs);

echo "edge cases\n";
check('an empty crontab gains just our block',
  cronSpliceBlock('', $ours),
  ZM_CRONTAB_BEGIN."\n".$ours[0]."\n".ZM_CRONTAB_END."\n");
check('an empty crontab and no schedules stays empty', cronSpliceBlock('', array()), '');
check('a crontab ends with a newline',
  substr(cronSpliceBlock($theirs, $ours), -1), "\n");
check('CRLF line endings do not survive as stray CRs',
  strpos(cronSpliceBlock("0 3 * * * /bin/true\r\n", $ours), "\r"), false);

// A write interrupted between the markers leaves a begin with no end. Treating
// everything after it as our block would swallow the user's entries, so the
// file is taken as-is and the orphan dropped.
$torn = "0 3 * * * /usr/local/bin/backup.sh\n".ZM_CRONTAB_BEGIN."\n";
$repaired = cronSpliceBlock($torn, $ours);
check('a torn block does not eat the entries after it',
  strpos($repaired, '/usr/local/bin/backup.sh') !== false, true);
check('and leaves exactly one begin marker',
  substr_count($repaired, ZM_CRONTAB_BEGIN), 1);

echo "entries after our block keep their place\n";
$sandwich = "0 1 * * * /bin/first\n".ZM_CRONTAB_BEGIN."\nold line\n".ZM_CRONTAB_END."\n0 2 * * * /bin/last\n";
$resliced = cronSpliceBlock($sandwich, $ours);
check('the line before stays before',
  strpos($resliced, '/bin/first') < strpos($resliced, ZM_CRONTAB_BEGIN), true);
check('the line after stays after',
  strpos($resliced, '/bin/last') > strpos($resliced, ZM_CRONTAB_END), true);
check('the old body is gone', strpos($resliced, 'old line'), false);

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
