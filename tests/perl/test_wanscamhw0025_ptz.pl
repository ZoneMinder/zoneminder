#!/usr/bin/perl
#
# ZoneMinder::Control::WanscamHW0025 relative moves and reset.
#
# The Controls row advertises CanMoveRel and not CanMoveCon, so the web ui
# builds its move commands as moveRelUp/moveRelDown/... .  The module only ever
# defined moveCon*, so every pan and tilt button was logged as "does not
# implement 'moveRelUp'" and ignored.  The commands it does send carry
# onestep=1, which is the camera's relative, one step move, so the moveCon*
# bodies are the right thing to call.
#
# The same row advertises CanReset, and the ui's Reset button sends the command
# 'reset', but the module only defines cameraReset, so the button did nothing.
#
# Run as: perl tests/perl/test_wanscamhw0025_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  # Optional CPAN modules that are only used by the config helpers.
  if (!eval { require URI::Encode; 1 }) {
    # Not installed here: define a pass-through at run time, so the stub
    # cannot collide with the real module where it is installed.
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use ZoneMinder::Control::WanscamHW0025;

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

package FakeResponse;
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new;
}

package FakeMonitor;
sub new { return bless {ControlAddress => '192.0.2.1:80', ControlDevice => '', AutoStopTimeout => 0}, $_[0]; }

package main;

sub camera {
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new, state => 'open',
  }, 'ZoneMinder::Control::WanscamHW0025';
  return ($control, $ua);
}

sub last_url {
  my ($method, $params) = @_;
  my ($control, $ua) = camera();
  $control->$method($params || {});
  return @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri.'' : '';
}

# The Controls row selects relative moves, so these are what the ui calls.
{
  my ($control) = camera();
  for my $name (qw(moveRelUp moveRelDown moveRelLeft moveRelRight
                   moveRelUpLeft moveRelUpRight moveRelDownLeft moveRelDownRight)) {
    check("$name is implemented", $control->can($name) ? 1 : 0, 1);
  }
}

# Each relative move is the same camera command as the continuous one.
check('moveRelUp steps up', last_url('moveRelUp'),
  'http://192.0.2.1:80/decoder_control.cgi?command=0&onestep=1&');
check('moveRelDown steps down', last_url('moveRelDown'),
  'http://192.0.2.1:80/decoder_control.cgi?command=2&onestep=1&');
check('moveRelLeft steps left', last_url('moveRelLeft'),
  'http://192.0.2.1:80/decoder_control.cgi?command=4&onestep=1&');
check('moveRelRight steps right', last_url('moveRelRight'),
  'http://192.0.2.1:80/decoder_control.cgi?command=6&onestep=1&');
check('moveRelUpLeft steps up left', last_url('moveRelUpLeft'),
  'http://192.0.2.1:80/decoder_control.cgi?command=90&onestep=1&');
check('moveRelUpRight steps up right', last_url('moveRelUpRight'),
  'http://192.0.2.1:80/decoder_control.cgi?command=91&onestep=1&');
check('moveRelDownLeft steps down left', last_url('moveRelDownLeft'),
  'http://192.0.2.1:80/decoder_control.cgi?command=92&onestep=1&');
check('moveRelDownRight steps down right', last_url('moveRelDownRight'),
  'http://192.0.2.1:80/decoder_control.cgi?command=93&onestep=1&');

# The Reset button sends 'reset'; cameraReset is the implementation.
{
  my ($control) = camera();
  check('reset is implemented', $control->can('reset') ? 1 : 0, 1);
  check('reset reboots the camera', last_url('reset'),
    'http://192.0.2.1:80/reboot.cgi?');
  check('cameraReset still reboots the camera', last_url('cameraReset'),
    'http://192.0.2.1:80/reboot.cgi?');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
