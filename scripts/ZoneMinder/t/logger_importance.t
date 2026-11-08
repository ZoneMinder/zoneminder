use strict;
use warnings;
use Test::More tests => 20;

require_ok('ZoneMinder::Logger');

my $L = 'ZoneMinder::Logger';
my $level = $L->can('importanceLevel');

# --- the scale ----------------------------------------------------------------
#
# Severity runs downwards: ERROR -2, WARNING -1, INFO 0, DEBUG1 1. Importance
# runs upwards: Normal 0, Less 1, Not 2. Adding one to the other is what makes
# a less important monitor quieter, and it is easy to get backwards, so pin
# the direction itself before anything else.

cmp_ok($L->ERROR(), '<', $L->WARNING(), 'error is more severe than warning');
cmp_ok($L->WARNING(), '<', $L->INFO(), 'warning is more severe than info');
cmp_ok($L->INFO(), '<', $L->DEBUG1(), 'info is more severe than debug');

# --- demoting an error --------------------------------------------------------

is($level->($L->ERROR(), 0), $L->ERROR(), 'a Normal monitor keeps the full level');
is($level->($L->ERROR(), 1), $L->WARNING(), 'a Less important monitor drops one step');
is($level->($L->ERROR(), 2), $L->INFO(), 'an unimportant monitor drops two');

# Demoted, not silenced. INFO is still logged at the levels ZoneMinder ships
# with, so marking a monitor unimportant must not quietly hide a device that
# has been broken for a month.
cmp_ok($level->($L->ERROR(), 2), '<=', $L->INFO(),
  'the least important monitor still reports at info or louder');
cmp_ok($level->($L->ERROR(), 2), '<', $L->DEBUG1(),
  'and never lands in debug, which is off by default');

# --- demoting a warning -------------------------------------------------------
#
# zmwatch.pl has always weighted its restart messages from a WARNING base, and
# at Not important that reaches DEBUG1 and stops being logged by default. That
# is long-standing behaviour rather than a bug to fix here, so it is pinned:
# importanceLevel must not have quietly acquired a floor that changes it.

is($level->($L->WARNING(), 0), $L->WARNING(), 'a Normal monitor keeps the warning');
is($level->($L->WARNING(), 1), $L->INFO(), 'a Less important monitor drops it to info');
is($level->($L->WARNING(), 2), $L->DEBUG1(),
  'an unimportant monitor drops it to debug, as zmwatch has always done');

# --- what counts as an importance ---------------------------------------------
#
# Not knowing how much a monitor matters is no reason to hide its faults, so
# anything unrecognised is treated as Normal.

is($level->($L->ERROR(), undef), $L->ERROR(), 'an absent importance reports in full');
is($level->($L->ERROR(), ''), $L->ERROR(), 'an empty importance reports in full');
is($level->($L->ERROR(), 'Normal'), $L->ERROR(),
  'an importance passed as a name rather than a number does not corrupt the level');
is($level->($L->ERROR(), -1), $L->ERROR(), 'a negative importance cannot promote the level');

# --- what a caller passes -----------------------------------------------------
#
# Callers hold a monitor and pass its ImportanceNumber, so the values arriving
# here are the ones that method returns. Pinned so a change to that mapping is
# caught rather than silently shifting every caller's log level.

require ZoneMinder::Monitor;
is(ZoneMinder::Monitor::ImportanceNumber({Importance => 'Normal'}), 0, 'Normal is importance 0');
is(ZoneMinder::Monitor::ImportanceNumber({Importance => 'Less'}), 1, 'Less is importance 1');
is(ZoneMinder::Monitor::ImportanceNumber({Importance => 'Not'}), 2, 'Not is importance 2');

ok($L->can('ErrorImportance') && $L->can('WarningImportance'),
  'the wrappers callers actually use are defined');
