#!/usr/bin/perl
#
# zmc renames incomplete.<container> to <Id>-video.<...>.<container> before it
# writes the new DefaultVideo to the Events row. An event it left open (killed
# between the two) is closed by ZoneMinder::Event::Close(), which has to point
# DefaultVideo at the file that exists, or the event cannot be played until it
# is deleted.
#
# The database is stubbed.
#
# Run as: sudo -u www-data perl tests/perl/test_event_close_default_video.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use File::Temp qw(tempdir);
use POSIX qw(strftime);

use ZoneMinder::Event;

my $failures = 0;
my $passes = 0;
my $saved;

{
  no warnings qw(redefine once);
  *ZoneMinder::Database::zmDbFetchOne = sub {
    return {EndDateTime => undef, EndTimeStamp => undef, Frames => undef,
            AlarmFrames => 0, TotScore => undef, MaxScore => undef};
  };
  *ZoneMinder::Event::save = sub { my ($self, $data) = @_; $saved = $data; return ''; };
}

package FakeMonitor { sub EventPrefix { return 'Event-' } }

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '(undef)')."\n  want: ".(defined $want ? $want : '(undef)')."\n";
  }
}

my $start = 1791659945;
sub event {
  my ($dir, %fields) = @_;
  return bless {
    Id => 41, MonitorId => 2, Name => 'New Event',
    StartDateTime => strftime('%Y-%m-%d %H:%M:%S', localtime($start)),
    Length => 4, Frames => 10, AlarmFrames => 0, TotScore => 0, MaxScore => 0,
    Notes => '', Path => $dir, Monitor => bless({}, 'FakeMonitor'),
    %fields,
  }, 'ZoneMinder::Event';
}

sub touch { open(my $f, '>', $_[0]) or die "$_[0]: $!"; close $f; }

# The final file exists, the row still names incomplete.mp4.
{
  my $dir = tempdir(CLEANUP => 1);
  touch("$dir/41-video.h264.mp4");
  $saved = undef;
  event($dir, DefaultVideo => 'incomplete.mp4')->Close();
  check('DefaultVideo is corrected to the final file', $$saved{DefaultVideo}, '41-video.h264.mp4');
}

# The row names a file that is there: left alone.
{
  my $dir = tempdir(CLEANUP => 1);
  touch("$dir/incomplete.mp4");
  $saved = undef;
  event($dir, DefaultVideo => 'incomplete.mp4')->Close();
  check('an existing DefaultVideo is not rewritten', exists $$saved{DefaultVideo} ? 'rewritten' : 'untouched', 'untouched');
}

# Nothing to point at: left alone.
{
  my $dir = tempdir(CLEANUP => 1);
  $saved = undef;
  event($dir, DefaultVideo => 'incomplete.mp4')->Close();
  check('DefaultVideo is kept when no video file is found', exists $$saved{DefaultVideo} ? 'rewritten' : 'untouched', 'untouched');
}

# A monitor whose OutputContainer is mkv or webm.
for my $video ('41-video.h264.mkv', '41-video.vp9.webm') {
  my $dir = tempdir(CLEANUP => 1);
  touch("$dir/$video");
  $saved = undef;
  event($dir, DefaultVideo => 'incomplete.mp4')->Close();
  check("DefaultVideo is corrected to $video", $$saved{DefaultVideo}, $video);

  my $Event = event($dir, DefaultVideo => undef);
  $Event->recover_timestamps($dir);
  check("recover_timestamps finds $video", $$Event{DefaultVideo}, $video);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
