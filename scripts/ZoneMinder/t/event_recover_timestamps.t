use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use Date::Parse;

plan skip_all => 'ffmpeg not available' if system('ffmpeg -version >/dev/null 2>&1');
eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 6;

# An event directory holding only an mp4 (no capture jpgs), as produced when
# ZM_SAVE_JPEGS is off. Duration is 3 seconds.
my $dir = tempdir(CLEANUP => 1);
my $mp4 = "$dir/1-video.h264.mp4";
is(system("ffmpeg -v error -y -f lavfi -i testsrc=duration=3:size=64x64:rate=10 '$mp4' >/dev/null 2>&1"),
  0, 'built a 3 second test mp4');

# mtime of the mp4 is when recording finished.
my $end = 1700000000;
utime($end, $end, $mp4) or die "utime: $!";
# Make the directory itself much older, so a fallback to its mtime is visible.
utime($end - 3600, $end - 3600, $dir) or die "utime: $!";

my $Event = new ZoneMinder::Event();
$Event->recover_timestamps($dir);

cmp_ok(abs($Event->Length() - 3), '<', 0.5, 'Length comes from the mp4 duration');
is($Event->DefaultVideo(), '1-video.h264.mp4', 'DefaultVideo is the mp4 we found');

my $start = Date::Parse::str2time($Event->StartDateTime());
my $stop = Date::Parse::str2time($Event->EndDateTime());
is($stop, $end, 'EndDateTime is the mp4 mtime, when recording finished');
cmp_ok(abs($start - ($end - 3)), '<=', 1,
  'StartDateTime is duration before the end, not the dir mtime');
cmp_ok(abs(($stop - $start) - $Event->Length()), '<=', 1,
  'Length agrees with EndDateTime - StartDateTime');
