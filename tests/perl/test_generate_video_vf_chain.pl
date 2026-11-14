#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
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
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

my $dir = tempdir(CLEANUP => 1);
my $fake = "$dir/ffmpeg";
open(my $fh, '>', $fake) or die $!;
print $fh "#!/bin/sh\necho \"\$@\" > '$dir/args'\nfor last; do :; done\n: > \"\$last\"\n";
close($fh);
chmod(0755, $fake);

{
  no warnings qw(redefine once);
  *ZoneMinder::Event::Path = sub { return $dir; };
  $ZoneMinder::Config::Config{ZM_PATH_FFMPEG} = $fake;
  $ZoneMinder::Config::Config{ZM_FFMPEG_INPUT_OPTIONS} = '';
  $ZoneMinder::Config::Config{ZM_FFMPEG_OUTPUT_OPTIONS} = '';
}

my $Event = bless {
  Id => 7, Name => 'Event', Width => 160, Height => 120,
  Frames => 30, FullLength => 3, DefaultVideo => '7-video.mp4',
}, 'ZoneMinder::Event';

sub args_for {
  my ($transforms) = @_;
  unlink("$dir/args");
  $Event->GenerateVideo(1, 0, 1, 0, 1, 'mp4', $transforms);
  open(my $in, '<', "$dir/args") or die "ffmpeg was not run: $!";
  my $line = <$in>;
  close($in);
  chomp $line;
  return $line;
}

my $args = args_for('hflip,vflip');
my @vf = $args =~ /-vf (\S+)/g;
check('two transforms are passed as one -vf option', scalar(@vf), 1);
check('the -vf option holds both transforms as one chain', $vf[0] // '', 'hflip,vflip');

$args = args_for('');
check('no transforms means no -vf option', ($args =~ /-vf/ ? 'has -vf' : 'no -vf'), 'no -vf');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
