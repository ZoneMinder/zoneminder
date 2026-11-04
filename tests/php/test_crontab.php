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

echo "saying it the way the editor does\n";
// The editor shows "Every <when> at <time>", never cron. These build the spec
// from what it collected.
check('every day at 8am', cronFromParts('daily', 8, 0), '0 8 * * *');
check('every monday at 7pm', cronFromParts('monday', 19, 0), '0 19 * * 1');
check('every sunday', cronFromParts('sunday', 6, 30), '30 6 * * 0');
check('every saturday', cronFromParts('saturday', 23, 59), '59 23 * * 6');
check('every weekday at 7am', cronFromParts('weekdays', 7, 0), '0 7 * * 1-5');
check('every weekend day', cronFromParts('weekends', 9, 15), '15 9 * * 0,6');
check('every hour at quarter past', cronFromParts('hourly', 0, 15), '15 * * * *');
check('midnight is a time like any other', cronFromParts('daily', 0, 0), '0 0 * * *');

echo "parts the editor could not have produced\n";
check('an unknown frequency', cronFromParts('fortnightly', 8, 0), null);
check('hour 24', cronFromParts('daily', 24, 0), null);
check('minute 60', cronFromParts('daily', 8, 60), null);
check('a negative hour', cronFromParts('daily', -1, 0), null);
check('a non-numeric time', cronFromParts('daily', 'eight', 0), null);

echo "loading a saved schedule back into the editor\n";
check('every day at 8am',
  cronToParts('0 8 * * *'), array('frequency'=>'daily', 'hour'=>8, 'minute'=>0));
check('every monday at 7pm',
  cronToParts('0 19 * * 1'), array('frequency'=>'monday', 'hour'=>19, 'minute'=>0));
check('every weekday',
  cronToParts('0 7 * * 1-5'), array('frequency'=>'weekdays', 'hour'=>7, 'minute'=>0));
check('every weekend day',
  cronToParts('15 9 * * 0,6'), array('frequency'=>'weekends', 'hour'=>9, 'minute'=>15));
check('every hour',
  cronToParts('15 * * * *'), array('frequency'=>'hourly', 'hour'=>0, 'minute'=>15));
// Cron accepts 7 for Sunday as well as 0, so a spec written either way loads.
check('sunday written as 7',
  cronToParts('0 6 * * 7'), array('frequency'=>'sunday', 'hour'=>6, 'minute'=>0));

echo "schedules the two controls cannot draw\n";
// These come back null so the editor falls back to showing the raw spec,
// rather than quietly rewriting someone's schedule into the nearest thing it
// happens to understand.
check('every fifteen minutes', cronToParts('*/15 * * * *'), null);
check('the first of the month', cronToParts('0 9 1 * *'), null);
check('one month only', cronToParts('0 9 * 6 *'), null);
check('a pair of weekdays', cronToParts('0 9 * * 1,3'), null);
check('hourly but only on tuesdays', cronToParts('0 * * * 2'), null);
check('an invalid spec is not parts either', cronToParts('nonsense'), null);

echo "round trip\n";
// Anything the editor can draw must survive being saved and loaded back,
// or editing a row would silently change when it runs.
$bad = array();
foreach (array_keys(cronFrequencies()) as $freq) {
  foreach (array(array(0, 0), array(8, 0), array(19, 30), array(23, 59)) as $t) {
    list($h, $m) = $t;
    $spec = cronFromParts($freq, $h, $m);
    if ($spec === null) { $bad[] = "$freq $h:$m did not build"; continue; }
    if (!cronScheduleIsValid($spec)) { $bad[] = "$freq $h:$m built invalid '$spec'"; continue; }
    $parts = cronToParts($spec);
    if ($parts === null) { $bad[] = "$freq $h:$m -> '$spec' did not load back"; continue; }
    $expect_hour = ($freq == 'hourly') ? 0 : $h;
    if (($parts['frequency'] !== $freq) or ($parts['hour'] !== $expect_hour)
        or ($parts['minute'] !== $m)) {
      $bad[] = "$freq $h:$m -> '$spec' -> ".json_encode($parts);
    }
  }
}
check('every schedule the editor can build survives a round trip', $bad, array());

echo "commands we will put in a crontab line\n";
// The command is not judged on what it does -- running things is the point --
// only on whether it stays on its own line and means in cron what it meant to
// whoever typed it.
check('a script', cronCommandIsValid('/usr/local/bin/backup.sh'), true);
check('a script with arguments', cronCommandIsValid('/usr/bin/zmaudit.pl -f -d 2'), true);
check('a pipeline', cronCommandIsValid('zmstats.pl | logger -t zm'), true);
check('an escaped percent, which is how cron spells one',
  cronCommandIsValid('date +\%F >> /tmp/zm.log'), true);

echo "commands we refuse\n";
check('empty', cronCommandIsValid(''), false);
check('only whitespace', cronCommandIsValid('   '), false);
check('not a string', cronCommandIsValid(null), false);
// A newline ends the entry, so the rest would become a crontab line of its own
// with a schedule of its own choosing.
check('a newline could add an entry of its own',
  cronCommandIsValid("echo hi\n0 0 * * * rm -rf /"), false);
check('a carriage return likewise', cronCommandIsValid("echo hi\r0 0 * * * id"), false);
// cron stops the command at an unescaped % and feeds the rest in as stdin, so
// accepting one would silently run less than was typed.
check('a bare percent truncates the command', cronCommandIsValid('date +%F'), false);
check('a bare percent anywhere', cronCommandIsValid('echo 50% done'), false);
check('a backslash cannot hide one', cronCommandIsValid('echo \\\\%F'), false);

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
