#!/usr/bin/perl
#
# ZoneMinder::Event::Close() finishes an event that a capture daemon left open
# when it was killed (EndDateTime NULL). It is what zmeventtool.pl close and
# zmwatch use, so it has to cope with what such an event looks like:
#
#  - zmc writes Frames rows in batches, so an event killed in its first seconds
#    has none;
#  - the event row's Length, Frames and scores are updated with each batch, so
#    the row or the Frames rows can be ahead of the other.
#
# The database is stubbed.
#
# Run as: sudo -u www-data perl tests/perl/test_event_close_recovered.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use POSIX qw(strftime);

use ZoneMinder::Event;

my $failures = 0;
my $passes = 0;

# What the stubbed Frames aggregate returns, and what Close() asked save() to
# write.
my $frame_row;
my $saved;

{
  no warnings qw(redefine once);
  *ZoneMinder::Database::zmDbFetchOne = sub { return $frame_row; };
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
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my $start = 1791659945;    # any whole second
my $start_str = strftime('%Y-%m-%d %H:%M:%S', localtime($start));
sub at { return strftime('%Y-%m-%d %H:%M:%S', localtime($start + $_[0])); }

sub event {
  my (%fields) = @_;
  return bless {
    Id => 41, MonitorId => 2, Name => 'New Event', StartDateTime => $start_str,
    Length => 0, Frames => 0, AlarmFrames => 0, TotScore => 0, MaxScore => 0,
    Notes => '', Monitor => bless({}, 'FakeMonitor'),
    %fields,
  }, 'ZoneMinder::Event';
}

my $no_frames = {EndDateTime => undef, EndTimeStamp => undef, Frames => undef,
                 AlarmFrames => 0, TotScore => undef, MaxScore => undef};

# Killed before the first Frames batch: the row is all there is.
{
  $frame_row = $no_frames; $saved = undef;
  my $event = event(Length => 4.6, Frames => 70, AlarmFrames => 3, TotScore => 45, MaxScore => 20);
  my $error = $event->Close();
  check('closes an event that has no Frames rows', $error, '');
  check('end is start plus the row Length, in whole seconds', $$saved{EndDateTime}, at(4));
  check('Length from the row', $$saved{Length}, 4.6);
  check('Frames from the row', $$saved{Frames}, 70);
  check('alarm frames and scores from the row', join(',', @$saved{qw(AlarmFrames TotScore AvgScore MaxScore)}), '3,45,15,20');
  check('name gets the recovered tag', $$saved{Name}, 'Event-41(r)');
  check('notes say it was recovered', $$saved{Notes}, ' Recovered.');
}

# The Frames rows end later than the row was last updated: as before.
{
  $frame_row = {EndDateTime => at(20), EndTimeStamp => $start + 20, Frames => 300,
                AlarmFrames => 2, TotScore => 30, MaxScore => 20};
  $saved = undef;
  my $event = event(Length => 19.93, Frames => 290, Notes => 'person');
  $event->Close();
  check('end from the last Frames row when it is later', $$saved{EndDateTime}, at(20));
  check('Length from the last Frames row when it is later', $$saved{Length}, 20);
  check('Frames from the Frames rows when higher', $$saved{Frames}, 300);
  check('alarm frames and scores from the Frames rows', join(',', @$saved{qw(AlarmFrames TotScore AvgScore MaxScore)}), '2,30,15,20');
  check('appends to existing notes', $$saved{Notes}, 'person Recovered.');
}

# The row is ahead of the Frames rows: zmc died before writing its last batch.
{
  $frame_row = {EndDateTime => at(15), EndTimeStamp => $start + 15, Frames => 250,
                AlarmFrames => 2, TotScore => 30, MaxScore => 25};
  $saved = undef;
  my $event = event(Length => 19.93, Frames => 300, AlarmFrames => 5, TotScore => 80, MaxScore => 20);
  $event->Close();
  check('Length from the row when it is longer', $$saved{Length}, 19.93);
  check('end is start plus that Length, in whole seconds', $$saved{EndDateTime}, at(19));
  check('Frames from the row when higher', $$saved{Frames}, 300);
  check('each score is the larger of the two', join(',', @$saved{qw(AlarmFrames TotScore AvgScore MaxScore)}), '5,80,16,25');
}

# The user renamed the event while it was recording: zmwatch closes it on its
# own now, so it keeps that name; the note still says it was recovered.
{
  $frame_row = $no_frames; $saved = undef;
  my $event = event(Name => 'Front door delivery', Length => 4.6, Frames => 70);
  $event->Close();
  check('keeps a name the user gave the event', exists $$saved{Name} ? $$saved{Name} : '(not changed)', '(not changed)');
  check('notes still say it was recovered', $$saved{Notes}, ' Recovered.');
}

# No start time: nothing to anchor the end to.
{
  $frame_row = $no_frames; $saved = undef;
  my $event = event(StartDateTime => undef);
  my $error = $event->Close();
  check('refuses an event without a start time', ($error and !$saved) ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
