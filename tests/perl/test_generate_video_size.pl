#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use File::Temp qw(tempdir);

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
  my ($scale, $size, $transforms) = @_;
  unlink("$dir/args");
  $Event->GenerateVideo(1, 0, $scale, $size, 1, 'mp4', $transforms);
  open(my $in, '<', "$dir/args") or die "ffmpeg was not run: $!";
  my $line = <$in>;
  close($in);
  chomp $line;
  return $line;
}

check('unscaled video is not forced to the input size', (args_for(1, 0, 'transpose=1') =~ /\s-s\s/ ? '-s' : 'no -s'), 'no -s');
check('scaled video gets the scaled size', (args_for(0.5, 0, '') =~ /-s (\S+)/)[0], '80x60');
check('scaled size is rounded down to even dimensions', (args_for(0.33, 0, '') =~ /-s (\S+)/)[0], '52x38');
check('absolute size is passed through', (args_for(0, '320x240', '') =~ /-s (\S+)/)[0], '320x240');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
