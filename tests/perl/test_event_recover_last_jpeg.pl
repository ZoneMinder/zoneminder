#!/usr/bin/perl
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
  sub INFO { return 1; }
  sub logLevel { return 0; }
  sub Debug { return 1; }
  sub Info { return 1; }
  sub Warning { return 1; }
  sub Error { return 1; }
  sub Fatal { return 1; }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
}

use ZoneMinder::Event;

use File::Temp qw(tempdir);
use POSIX qw(mktime);

my $failures = 0;
my $passes = 0;

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

{
  package FakeDbh;
  sub begin_work { 1 }
  sub commit { 1 }
}
$ZoneMinder::Database::dbh = bless {}, 'FakeDbh';
{
  no warnings qw(redefine once);
  # Every frame is already in the database, so nothing is saved
  *ZoneMinder::Frame::find_one = sub { return 1; };
}

# An event of 1500 jpegs saved with ZM_EVENT_IMAGE_DIGITS at 3: frame numbers
# past 999 are wider than the padding, so they do not sort as text.
my $path = tempdir(CLEANUP => 1);
my $start = mktime(0, 0, 9, 2, 10, 126);
for my $frame (1 .. 1500) {
  my $file = sprintf('%s/%03d-capture.jpg', $path, $frame);
  open(my $fh, '>', $file) or die "Can't write $file: $!";
  close($fh);
  my $time = $start + int(($frame - 1) / 10);
  utime($time, $time, $file);
}

my $event = bless {Id => 7, Scheme => 'Medium', MonitorId => 1}, 'ZoneMinder::Event';
$event->recover_timestamps($path);
check('the event starts at its first frame', $event->{StartDateTime}, '2026-11-02 09:00:00');
check('the event ends at its last frame', $event->{EndDateTime}, '2026-11-02 09:02:29');
check('the length runs to the last frame', $event->{Length}, 149);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
