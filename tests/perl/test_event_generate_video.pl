#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use Cwd ();
use File::Temp qw(tempdir);

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config = (
    ZM_EVENT_IMAGE_DIGITS => 5,
    ZM_FFMPEG_INPUT_OPTIONS => '',
    ZM_FFMPEG_OUTPUT_OPTIONS => '',
  );
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

my $dir = tempdir(CLEANUP => 1);
my $event_dir = "$dir/event";
mkdir($event_dir);

# A stand-in for ffmpeg that records its arguments.
my $args_file = "$dir/args";
my $ffmpeg = "$dir/ffmpeg";
open(my $fh, '>', $ffmpeg) or die "Can't write $ffmpeg: $!";
print $fh "#!/bin/sh\nprintf '%s\\n' \"\$@\" > '$args_file'\n";
close($fh);
chmod(0755, $ffmpeg);
$ZoneMinder::Config::Config{ZM_PATH_FFMPEG} = $ffmpeg;

sub frame_rate {
  my %event = @_;
  unlink($args_file);
  my $event = bless {
    Id => 1, Name => 'Event-1', Path => $event_dir, Width => 640, Height => 480,
    DefaultVideo => '', %event,
  }, 'ZoneMinder::Event';
  my $video = eval { $event->GenerateVideo(0, 0, 0, 0, 1, 'mp4', ''); };
  return 'died' if $@;
  return 'no video' if !$video;
  open(my $args, '<', $args_file) or return 'ffmpeg not run';
  my @args = <$args>;
  close($args);
  chomp(@args);
  my ($i) = grep { $args[$_] eq '-r' } 0..$#args;
  return $args[$i+1];
}

my $cwd = Cwd::getcwd();
check('frame rate comes from the span of the frames',
  frame_rate(Frames => 10, FullLength => 5, Length => 5), '2.00');
check('an event with one frame does not die',
  frame_rate(Frames => 1, FullLength => 0, Length => 0), '1');
check('an event with no Frames rows falls back to the event length',
  frame_rate(Frames => 20, FullLength => undef, Length => 10), '2.00');
chdir($cwd);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
