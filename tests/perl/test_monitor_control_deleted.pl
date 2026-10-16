#!/usr/bin/perl
#
# ZoneMinder::Monitor::control() must not start or restart a capture daemon for
# a monitor that has been deleted (or removed) since the caller loaded it.
#
# zmwatch fetches its monitor list at the top of a pass and then walks it. A
# monitor deleted mid-pass is still in that stale list, its shared memory is
# already gone because the web ui stopped zmc, so zmwatch restarts it - leaving
# an orphaned zmc for a deleted monitor that nothing will ever stop again.
#
# Run as: sudo -u www-data perl tests/perl/test_monitor_control_deleted.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Monitor;

my $failures = 0;
my $passes = 0;

# Commands control() would have handed to zmdc.pl.
my @commands;
# What the stubbed db says about the monitor: a hashref row, or undef for gone.
my $db_row;

{
  no warnings 'redefine';
  # zmDbFetchOne is imported into ZoneMinder::Monitor, so override it there.
  *ZoneMinder::Monitor::zmDbFetchOne = sub { return $db_row; };
  *ZoneMinder::General::runCommand = sub { push @commands, $_[0]; return ''; };
}

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

sub run_control {
  my ($deleted_row, $command) = @_;
  $db_row = $deleted_row;
  @commands = ();
  my $monitor = bless {Id => 7, Name => 'test', Type => 'Ffmpeg'}, 'ZoneMinder::Monitor';
  $monitor->control($command);
  return join('|', @commands);
}

check('start on a live monitor still starts zmc',
  run_control({Deleted => 0}, 'start'), 'zmdc.pl start zmc -m 7');

check('restart on a live monitor still restarts zmc',
  run_control({Deleted => 0}, 'restart'), 'zmdc.pl restart zmc -m 7');

check('start on a deleted monitor is skipped',
  run_control({Deleted => 1}, 'start'), '');

check('restart on a deleted monitor is skipped',
  run_control({Deleted => 1}, 'restart'), '');

check('restart on a monitor removed from the db is skipped',
  run_control(undef, 'restart'), '');

# Stopping must always be allowed - that is how an orphan gets cleaned up.
check('stop on a deleted monitor still stops zmc',
  run_control({Deleted => 1}, 'stop'), 'zmdc.pl stop zmc -m 7');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
