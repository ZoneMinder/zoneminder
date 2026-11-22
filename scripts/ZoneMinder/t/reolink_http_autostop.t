use strict;
use warnings;
use Test::More tests => 11;

require_ok('ZoneMinder::Control::Reolink_HTTP');
my $P = 'ZoneMinder::Control::Reolink_HTTP';

# ---------------------------------------------------------------------------
# A monitor that just counts motion detection suspensions and resumes. The
# module only needs the two methods and ModectDuringPTZ.
# ---------------------------------------------------------------------------
{
  package Fake::Monitor;
  our $SUSPENDS = 0;
  our $RESUMES  = 0;
  sub suspendMotionDetection { $SUSPENDS++ }
  sub resumeMotionDetection  { $RESUMES++ }
}

my @sent;
no warnings 'redefine', 'once';
local *ZoneMinder::Control::Reolink_HTTP::sendApiCmd = sub {
  my ($self, $cmd) = @_;
  push @sent, $cmd->{param}->{op};
  return 1;
};

sub new_control {
  my %monitor = (%{shift()}, ModectDuringPTZ => 0);
  return bless {
    channel => 0,
    Monitor => bless({%monitor}, 'Fake::Monitor'),
  }, $P;
}

sub reset_counters {
  @sent = ();
  $Fake::Monitor::SUSPENDS = 0;
  $Fake::Monitor::RESUMES  = 0;
}

# --- move ----------------------------------------------------------------
# A move with autostop must be followed by a stop, and the stop resumes the
# motion detection that the move suspended.
reset_counters();
my $c = new_control({AutoStopTimeout => 1});
$c->moveConUp({autostop => 1, tiltspeed => 5});
is_deeply(\@sent, ['Up', 'Stop'], 'moveConUp with autostop sends Up then Stop');
is($Fake::Monitor::SUSPENDS, 1, 'the move suspended motion detection');
is($Fake::Monitor::RESUMES, 1, 'and the auto stop resumed it');

# Without autostop the camera is left moving, as it always was.
reset_counters();
$c = new_control({AutoStopTimeout => 1});
$c->moveConUp({autostop => 0, tiltspeed => 5});
is_deeply(\@sent, ['Up'], 'moveConUp without autostop sends only Up');
is($Fake::Monitor::RESUMES, 0, 'so motion detection stays suspended');

# No timeout configured means nothing to wait for.
reset_counters();
$c = new_control({});
$c->moveConLeft({autostop => 1, panspeed => 5});
is_deeply(\@sent, ['Left'], 'no AutoStopTimeout means no auto stop');

# --- zoom ----------------------------------------------------------------
reset_counters();
$c = new_control({AutoStopTimeout => 1});
$c->zoomConTele({autostop => 1, speed => 5});
is_deeply(\@sent, ['ZoomInc', 'Stop'], 'zoomConTele with autostop sends the zoom stop');

reset_counters();
$c = new_control({AutoStopTimeout => 1});
$c->zoomConWide({autostop => 0, speed => 5});
is_deeply(\@sent, ['ZoomDec'], 'zoomConWide without autostop sends no stop');

# --- focus ---------------------------------------------------------------
reset_counters();
$c = new_control({AutoStopTimeout => 1});
$c->focusConNear({autostop => 1, speed => 5});
is_deeply(\@sent, ['FocusDec', 'Stop'], 'focusConNear with autostop sends the focus stop');

reset_counters();
$c = new_control({AutoStopTimeout => 1});
$c->focusConFar({autostop => 0, speed => 5});
is_deeply(\@sent, ['FocusInc'], 'focusConFar without autostop sends no stop');
