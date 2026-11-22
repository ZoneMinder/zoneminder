<?php
// Tests ZM_ALLOWED_COMMANDS: which commands a filter or a scheduled task may
// run.
//
// Two places in ZoneMinder run a command the operator typed -- a filter with
// "Execute command", and a scheduled task -- and both run it as the web user
// through a shell. The setting narrows that to a list the administrator chose.
//
// The rule exists twice, in web/includes/Command.php and in
// ZoneMinder::General, because the web ui refuses a command when it is saved
// and zmfilter.pl must refuse one when it is about to run. Two
// implementations of one rule drift, so every case below is put to both and
// they must agree: a disagreement means one of them is letting something
// through that the other stops.
//
// Run: php tests/php/test_allowed_commands.php   (from the repo root)

require_once(__DIR__.'/../../web/includes/Command.php');

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

// Every case is (command, allow-list, expected). Used for the PHP checks and
// then replayed through the Perl implementation.
$cases = array(
  // No list configured: unrestricted, which is what every existing install
  // has and must keep having after an upgrade.
  array('/usr/local/bin/anything.sh', '', true, 'no list means no restriction'),
  array('rm -rf /', '', true, 'no list really does mean no restriction'),
  array('anything; else', '   ', true, 'a list of only whitespace is no list'),

  // With a list, the program must be on it.
  array('/usr/local/bin/backup.sh', '/usr/local/bin/backup.sh', true, 'the listed program'),
  array('/usr/bin/zmaudit.pl', '/usr/local/bin/backup.sh,/usr/bin/zmaudit.pl', true,
        'the second entry on the list'),
  array('/usr/bin/other', '/usr/local/bin/backup.sh', false, 'a program not on the list'),
  array('backup.sh', '/usr/local/bin/backup.sh', false,
        'the same name by a different path is a different program'),
  array('/usr/local/bin/backup.sh.evil', '/usr/local/bin/backup.sh', false,
        'a longer name that merely starts the same'),

  // Arguments must still be allowed: zmfilter.pl appends the event path to
  // whatever the filter stored, so an exact whole-command match could never
  // work for filters.
  array('/usr/local/bin/backup.sh --full', '/usr/local/bin/backup.sh', true,
        'arguments are fine, only the program is matched'),
  array('/usr/local/bin/backup.sh /var/cache/zoneminder/events/1/2026-10-04/99',
        '/usr/local/bin/backup.sh', true, 'the event path zmfilter appends'),

  // Chaining is refused while a list is in force: the program being on the
  // list says nothing about what else would run alongside it.
  array('/usr/local/bin/backup.sh; rm -rf /', '/usr/local/bin/backup.sh', false,
        'a semicolon starts a second command'),
  array('/usr/local/bin/backup.sh && rm -rf /', '/usr/local/bin/backup.sh', false,
        'so does an ampersand'),
  array('/usr/local/bin/backup.sh | mail attacker', '/usr/local/bin/backup.sh', false,
        'so does a pipe'),
  array('/usr/local/bin/backup.sh `id`', '/usr/local/bin/backup.sh', false,
        'so does a backtick'),
  array('/usr/local/bin/backup.sh $(id)', '/usr/local/bin/backup.sh', false,
        'so does a dollar-paren'),

  // Redirection does not start a program, and refusing it would break the
  // ordinary thing people write.
  array('/usr/local/bin/backup.sh >> /var/log/mine.log', '/usr/local/bin/backup.sh', true,
        'redirection is not chaining'),

  // Spelling.
  array('  /usr/local/bin/backup.sh  ', '/usr/local/bin/backup.sh', true,
        'surrounding whitespace does not change the program'),
  array('"/usr/local/bin/my script.sh"', '/usr/local/bin/my script.sh', true,
        'a quoted path is the same program'),
  array('/usr/bin/zmaudit.pl', ' /usr/local/bin/backup.sh , /usr/bin/zmaudit.pl ', true,
        'whitespace around list entries is ignored'),

  // Nothing to run.
  array('', '/usr/local/bin/backup.sh', false, 'an empty command is not on any list'),
  array('   ', '/usr/local/bin/backup.sh', false, 'nor is whitespace'),

  // A leading environment assignment is not the listed program, and is
  // refused rather than looked past.
  array('FOO=1 /usr/local/bin/backup.sh', '/usr/local/bin/backup.sh', false,
        'a leading assignment is not the program'),
);

echo "the rule, in PHP\n";
foreach ($cases as $case) {
  list($command, $allowed, $want, $label) = $case;
  check($label, commandIsAllowed($command, $allowed), $want);
}

echo "picking the program out of a command\n";
check('a bare path', commandProgram('/bin/true'), '/bin/true');
check('with arguments', commandProgram('/bin/echo hello world'), '/bin/echo');
check('quotes are not part of the name', commandProgram('"/bin/my prog"'), '/bin/my prog');
check('nothing to pick', commandProgram('   '), null);
check('not a string', commandProgram(null), null);

echo "the same rule, in Perl\n";
// ZoneMinder::General is where zmfilter.pl gets it. Driven as a subprocess
// because that is the only honest way to ask the other implementation.
$lib = escapeshellarg(__DIR__.'/../../scripts/ZoneMinder/lib');
$disagreements = array();
$perl_ran = 0;
foreach ($cases as $case) {
  list($command, $allowed, $want, $label) = $case;
  $script = 'use ZoneMinder::General qw(:all); '.
            'print commandIsAllowed($ARGV[0], $ARGV[1]) ? "1" : "0";';
  $cmd = 'perl -I'.$lib.' -e '.escapeshellarg($script).' '.
         escapeshellarg($command).' '.escapeshellarg($allowed).' 2>/dev/null';
  $out = shell_exec($cmd);
  if ($out === null or $out === '') {
    $disagreements[] = "$label: perl produced no answer";
    continue;
  }
  $perl_ran++;
  $perl = (trim($out) === '1');
  if ($perl !== $want) $disagreements[] = "$label: perl said ".($perl?'allowed':'refused');
}
check('perl answered every case', $perl_ran, count($cases));
check('perl and php agree on every case', $disagreements, array());

echo "the two chaining lists are the same list\n";
// Read out of the Perl source rather than restated here, since a copy in the
// test would drift just as happily as the implementations would.
$general = file_get_contents(__DIR__.'/../../scripts/ZoneMinder/lib/ZoneMinder/General.pm');
preg_match('/our \@COMMAND_CHAINING = \((.*?)\);/s', $general, $m);
$perl_chaining = array();
if (isset($m[1])) {
  preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $m[1], $entries);
  $perl_chaining = $entries[1];
}
check('perl declares a chaining list', count($perl_chaining) > 0, true);
check('and it matches the php one', $perl_chaining, ZM_COMMAND_CHAINING);

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
