#!/usr/bin/perl
#
# Monitor::save must not turn an empty or NULL ONVIF_Alarm_Text back into
# 'MotionAlarm'. zmc treats both as "match every ONVIF event".
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  our $logger = bless {}, 'ZoneMinder::Logger::Null';
  sub Debug {} sub Info {} sub Warning {} sub Error {} sub Fatal {} sub INFO { 1 }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
    *{$caller.'::logger'} = \$logger;
  }
  package ZoneMinder::Logger::Null;
  sub AUTOLOAD { 1 } sub DESTROY {}
  package ZoneMinder::Memory;
  sub zmMemKey { return undef; }
  sub zmMemInvalidate { return 1; }
}

use ZoneMinder::Monitor;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my %written;
my $mock_dbh = bless {}, 'MockDBH';
{
  no warnings qw(redefine once);
  *MockDBH::prepare = sub { return bless { sql => $_[1] }, 'MockSTH'; };
  *MockDBH::selectrow_hashref = sub { return undef; };
  *MockDBH::last_insert_id = sub { return 99; };
  *MockSTH::execute = sub {
    my ($sth, @bind) = @_;
    if ($sth->{sql} =~ /^UPDATE `Monitors` SET (.*) WHERE/) {
      my @cols = $1 =~ /`(\w+)` = \?/g;
      @written{@cols} = @bind;
    } elsif ($sth->{sql} =~ /^INSERT INTO `Monitors` \((.*?)\) VALUES/) {
      my @cols = $1 =~ /`(\w+)`/g;
      @written{@cols} = @bind;
    }
    return 1;
  };
  *ZoneMinder::Database::start_transaction = sub { 0 };
  *ZoneMinder::Database::end_transaction = sub { 0 };
}
$ZoneMinder::Database::dbh = $mock_dbh;

# What zmpkg does when it applies a run state: change Capturing, Analysing and
# Recording on a monitor read from the database.
my $next_id = 10;
sub save_state_change {
  my ($alarm_text) = @_;
  %written = ();
  my $id = $next_id++;
  my $monitor = ZoneMinder::Monitor->new($id, {
      Id => $id, Name => 'cam', ONVIF_Alarm_Text => $alarm_text,
      Capturing => 'Always', Analysing => 'Always', Recording => 'Always',
    });
  my $err = $monitor->save({ Capturing => 'None', Analysing => 'None', Recording => 'None' });
  check('save succeeds', $err, '');
  check('state change is written', $written{Capturing}, 'None');
}

save_state_change('');
check('empty ONVIF_Alarm_Text stays empty', $written{ONVIF_Alarm_Text}, '');

save_state_change(undef);
check('NULL ONVIF_Alarm_Text stays NULL', $written{ONVIF_Alarm_Text}, undef);

save_state_change('DetectedSound');
check('a configured ONVIF_Alarm_Text is kept', $written{ONVIF_Alarm_Text}, 'DetectedSound');

# A monitor that is not in the database yet still starts with the default.
%written = ();
my $new_monitor = bless { Name => 'new' }, 'ZoneMinder::Monitor';
check('insert succeeds', $new_monitor->save(), '');
check('a new monitor gets the default ONVIF_Alarm_Text', $written{ONVIF_Alarm_Text}, 'MotionAlarm');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
