#!/usr/bin/perl
#
# Frame->Event and Event_Summary->Monitor build objects of classes their own
# files never loaded: Frame.pm calls new ZoneMinder::Event and Event_Summary.pm
# calls new ZoneMinder::Monitor, but neither requires that module. It only
# worked when the caller happened to have loaded ZoneMinder::Event or
# ZoneMinder::Monitor first (zmfilter does, so nothing noticed). A script that
# only does `use ZoneMinder::Frame` died with:
#   Can't locate object method "new" via package "ZoneMinder::Event"
# Both methods now require their class at runtime, which is safe despite
# Event.pm requiring Frame.pm (a compile-time use would be circular).
#
# Run as:
#   perl -I scripts/ZoneMinder/lib tests/perl/test_frame_event_summary_require.pl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

# new() loads the row from the database, which is irrelevant here and must not
# need a server. Stub the ZoneMinder infrastructure the way the other
# tests/perl tests do, plus a dbh that answers every lookup with an empty row
# so load() finds no data and leaves the object holding the id it was built
# with.
BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Database.pm'} = 1;

  package ZoneMinder::Base;
  our $VERSION = '0';

  package ZoneMinder::Config;
  our %Config;
  # Memory.pm runs this at load time to work out the architecture.
  %Config = (ZM_PATH_UNAME => '/bin/uname');
  sub import {
    no strict 'refs';
    *{caller().'::Config'} = \%Config;
  }

  package ZoneMinder::Logger;
  sub logLevel { return 0; }
  sub DEBUG { return 1; }
  sub INFO { return 1; }
  sub Debug { return 1; }
  sub Info { return 1; }
  sub Warning { return 1; }
  sub Error { return 1; }
  sub Fatal { return 1; }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal DEBUG INFO);
  }

  package ZoneMinder::Database;
  our $dbh;
  sub import {
    no strict 'refs';
    *{caller().'::dbh'} = \$dbh;
  }

  package TestDBH;
  sub selectrow_hashref { return {}; }
  sub errstr { return undef; }

  $ZoneMinder::Database::dbh = bless {}, 'TestDBH';
}

use ZoneMinder::Frame;
use ZoneMinder::Event_Summary;

my $passes = 0;
my $failures = 0;

sub ok {
  my ($name, $cond, $detail) = @_;
  if ($cond) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  $detail\n" if defined $detail;
  }
}

ok('neither class is loaded up front',
   !$INC{'ZoneMinder/Event.pm'} && !$INC{'ZoneMinder/Monitor.pm'},
   'Event or Monitor was already loaded, so the test proves nothing');

# Event_Summary->Monitor is checked first: ZoneMinder::Event requires
# ZoneMinder::Monitor, so calling Frame->Event first would load both and hide
# a missing require in Event_Summary.pm.
my $summary = bless { MonitorId => 1 }, 'ZoneMinder::Event_Summary';
my $monitor = eval { $summary->Monitor };
my $monitor_error = $@;
ok('Event_Summary->Monitor does not die', !$monitor_error, $monitor_error);
ok('Event_Summary->Monitor returns a ZoneMinder::Monitor',
   ref $monitor && ref $monitor eq 'ZoneMinder::Monitor',
   'got '.(ref($monitor) || 'no object'));
ok('Event_Summary->Monitor carries the MonitorId',
   $monitor && $monitor->{Id} && $monitor->{Id} == 1);
ok('ZoneMinder::Monitor got loaded',
   exists $INC{'ZoneMinder/Monitor.pm'});

my $frame = bless { EventId => 1 }, 'ZoneMinder::Frame';
my $event = eval { $frame->Event };
my $event_error = $@;
ok('Frame->Event does not die', !$event_error, $event_error);
ok('Frame->Event returns a ZoneMinder::Event',
   ref $event && ref $event eq 'ZoneMinder::Event',
   'got '.(ref($event) || 'no object'));
ok('Frame->Event carries the EventId',
   $event && $event->{Id} && $event->{Id} == 1);
ok('ZoneMinder::Event got loaded',
   exists $INC{'ZoneMinder/Event.pm'});

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
