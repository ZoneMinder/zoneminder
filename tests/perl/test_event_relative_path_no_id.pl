#!/usr/bin/perl
# Event::RelativePath() for the Shallow and Medium schemes ends in the event id.
# With no id it came out as '3/' or '3/<date>/', the monitor's whole directory,
# which delete_files() would then remove. It must be empty instead.
# Set ZM_TEST_ROOT to the root of the tree to test (default: this repository).
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  $INC{$_} = 1 for qw(ZoneMinder/Storage.pm ZoneMinder/Object.pm ZoneMinder/Frame.pm ZoneMinder/Monitor.pm
    ZoneMinder/Event_Tag.pm ZoneMinder/Tag.pm Number/Bytes/Human.pm Date/Manip.pm Date/Parse.pm);
  package ZoneMinder::Object;
  sub new { my $c = shift; return bless {}, $c; }
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config = (ZM_EVENT_IMAGE_DIGITS => 5);
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  our @errors;
  sub INFO { 1 } sub logLevel { 0 } sub Debug { 1 } sub Info { 1 } sub Warning { 1 } sub Fatal { 1 }
  sub Error { push @errors, $_[0]; 1 }
  sub import {
    no strict 'refs';
    my $c = caller();
    *{$c.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
}

use ZoneMinder::Event;

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

{
  no warnings 'redefine';
  *ZoneMinder::Event::Time = sub { 1700000000 };
}

sub path_for {
  my ($scheme, $id) = @_;
  my $event = bless {MonitorId => 3, Scheme => $scheme}, 'ZoneMinder::Event';
  $event->{Id} = $id if defined $id;
  return $event->RelativePath();
}

check('Shallow path ends in the event id', path_for('Shallow', 7) eq '3/7');
check('Medium path ends in the event id', path_for('Medium', 7) =~ m{^3/\d{4}-\d{2}-\d{2}/7$});
check('Shallow path with no id is empty', path_for('Shallow', undef) eq '');
check('Medium path with no id is empty', path_for('Medium', undef) eq '');
check('Medium path with id 0 is empty', path_for('Medium', 0) eq '');

exit($failures ? 1 : 0);
