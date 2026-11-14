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
use File::Path qw(make_path);
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
  no warnings 'redefine';
  *ZoneMinder::Event::mp4_duration = sub { return 3.4; };
}

# A Deep event with only a video: the video is finished 3 seconds after the
# event started, but its length is not a whole number of seconds.
my $root = tempdir(CLEANUP => 1);
my $path = "$root/1/26/11/02/09/30/00";
make_path($path);
my $video = "$path/7-video.mp4";
open(my $fh, '>', $video) or die "Can't write $video: $!";
close($fh);
my $finished = mktime(3, 30, 9, 2, 10, 126);
utime($finished, $finished, $video);

my $event = bless {Id => 7, Scheme => 'Deep', MonitorId => 1}, 'ZoneMinder::Event';
$event->recover_timestamps($path);
check('a Deep event starts at the time its directory is named for',
  $event->{StartDateTime}, '2026-11-02 09:30:00');
check('the video length is kept', $event->{Length}, '3.40');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
