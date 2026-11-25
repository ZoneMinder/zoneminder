#!/usr/bin/perl
#
# ZoneMinder::Control::HikVision request shapes, reset and credentials.
#
# Three things this checks:
#  - The Controls row advertises CanReset=1, but the module had no reset(), so
#    the web ui's Reset button sent a command the daemon silently ignored.
#  - A continuous stop was sent as an empty <PTZData/>, which some cameras
#    ignore and keep moving. A stop has to say pan/tilt/zoom = 0.
#  - ControlAddress/Path hold a URL, so a password with @ or : is
#    percent-encoded. That form was handed to Digest auth and rejected.
#
# No network: LWP::UserAgent is replaced and get_realm() is stubbed.
#
# Run as: perl tests/perl/test_hikvision_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_LIB_DIR} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

BEGIN {
  # Optional CPAN modules; only the config/firmware helpers use them.
  if (!eval { require URI::Encode; 1 }) {
    # Not installed here: define a pass-through at run time, so the stub
    # cannot collide with the real module where it is installed.
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
  if (!eval { require XML::LibXML; 1 }) {
    delete $INC{'XML/LibXML.pm'};
    $INC{'XML/LibXML.pm'} = __FILE__;
  }
}

use ZoneMinder::Control::HikVision;

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

# For checks about credentials: never print the value.
sub check_ok {
  my ($name, $ok) = @_;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
  }
}

package FakeUA;
sub new { return bless {creds => []}, $_[0]; }
sub cookie_jar { }
sub credentials {
  my ($self, @args) = @_;
  push @{$self->{creds}}, [@args];
}
sub get { die "FakeUA::get called - the test must not make a request\n"; }
sub request { die "FakeUA::request called - the test must not make a request\n"; }

package main;

# open() builds its own user agent; hand it one that records credentials.
{
  no warnings qw(redefine once);
  *LWP::UserAgent::new = sub { return FakeUA->new(); };
  # open() verifies the connection by fetching deviceInfo. Pretend it worked.
  *ZoneMinder::Control::HikVision::get_realm = sub { return 1; };
}

# Every command the module sends, as [cmd, body].
my @puts;
{
  no warnings 'redefine';
  *ZoneMinder::Control::HikVision::PutCmd = sub {
    push @puts, [ $_[1], $_[2] ];
    return;
  };
}

sub control {
  my (%monitor) = @_;
  $monitor{AutoStopTimeout} = 0 if !defined $monitor{AutoStopTimeout};
  return bless {Monitor => {%monitor}}, 'ZoneMinder::Control::HikVision';
}

sub body { return defined $_[0][1] ? $_[0][1] : '(undef)'; }

# --- continuous move and stop bodies -------------------------------------

@puts = ();
control()->moveStop({});
check('moveStop uses the continuous endpoint', $puts[0][0], 'ISAPI/PTZCtrl/channels/1/continuous');
check('moveStop sends pan/tilt/zoom = 0',
  body($puts[0]),
  '<PTZData><pan>0</pan><tilt>0</tilt><zoom>0</zoom></PTZData>');

@puts = ();
control()->zoomStop({});
check('zoomStop uses the continuous endpoint', $puts[0][0], 'ISAPI/PTZCtrl/channels/1/continuous');
check('zoomStop sends pan/tilt/zoom = 0',
  body($puts[0]),
  '<PTZData><pan>0</pan><tilt>0</tilt><zoom>0</zoom></PTZData>');

@puts = ();
control()->moveConUp({tiltspeed => 50});
check('moveConUp still sends only the moving axis', body($puts[0]),
  '<PTZData><tilt>50</tilt></PTZData>');

@puts = ();
control()->moveConRight({panspeed => 100});
check('moveConRight still sends only the moving axis', body($puts[0]),
  '<PTZData><pan>100</pan></PTZData>');

@puts = ();
control()->moveConDownRight({panspeed => 100, tiltspeed => 50});
check('moveConDownRight still sends both axes', body($puts[0]),
  '<PTZData><pan>100</pan><tilt>-50</tilt></PTZData>');

@puts = ();
control()->zoomConTele({speed => 25});
check('zoomConTele still sends the zoom axis', body($puts[0]),
  '<PTZData><zoom>25</zoom></PTZData>');

# --- reset ---------------------------------------------------------------

{
  my $c = control();
  check_ok('module implements reset (the Controls row advertises CanReset)',
    $c->can('reset') ? 1 : 0);
  @puts = ();
  $c->reset() if $c->can('reset');
  check('reset PUTs the ISAPI reboot endpoint', ($puts[0] // [])->[0],
    'ISAPI/System/reboot');
}

# --- percent-encoded credentials ----------------------------------------

{
  my $c = control(ControlAddress => 'us%40er:p%40ss%3Aword@203.0.113.9:8000');
  $c->open();
  check_ok('ControlAddress username is percent-decoded',
    (($c->{username} // '') eq 'us@er') ? 1 : 0);
  check_ok('ControlAddress password is percent-decoded',
    (($c->{password} // '') eq 'p@ss:word') ? 1 : 0);
  check_ok('the user agent is given the decoded credentials',
    ($c->{ua} and $c->{ua}{creds}[0]
      and $c->{ua}{creds}[0][2] eq 'us@er'
      and $c->{ua}{creds}[0][3] eq 'p@ss:word') ? 1 : 0);
}

{
  my $c = control(ControlAddress => 'admin:secret@203.0.113.9');
  $c->open();
  check_ok('plain ControlAddress credentials are left alone',
    (($c->{username} // '') eq 'admin'
      and ($c->{password} // '') eq 'secret') ? 1 : 0);
}

{
  # A raw colon in the password must not be mistaken for the user/pass
  # separator: userinfo splits on the first colon only.
  my $c = control(ControlAddress => 'admin:pa:ss@192.168.1.5');
  $c->open();
  check('raw colons in the ControlAddress password keep the username', $c->{username}, 'admin');
  check('raw colons in the ControlAddress password keep the password', $c->{password}, 'pa:ss');
}

{
  # Username-only userinfo: set the username, leave the password undefined.
  my $c = control(ControlAddress => 'admin@192.168.1.5');
  my $ok = eval { $c->open(); 1 };
  check_ok('username-only ControlAddress does not die', $ok ? 1 : 0);
  check('username-only ControlAddress sets the username', $c->{username}, 'admin');
  check_ok('username-only ControlAddress leaves the password undefined',
    !defined $c->{password} ? 1 : 0);
}

{
  my $c = control(Path => 'rtsp://us%40er:p%40ss%3Aword@203.0.113.9/Streaming/Channels/101');
  $c->open();
  check_ok('Path username is percent-decoded',
    (($c->{username} // '') eq 'us@er') ? 1 : 0);
  check_ok('Path password is percent-decoded',
    (($c->{password} // '') eq 'p@ss:word') ? 1 : 0);
}

{
  my $c = control(ControlAddress => '203.0.113.9', User => 'admin', Pass => 'secret');
  $c->open();
  check_ok('ControlAddress without userinfo falls back to the monitor user/pass',
    (($c->{username} // '') eq 'admin'
      and ($c->{password} // '') eq 'secret') ? 1 : 0);
}

{
  # Monitor User/Pass are stored as typed, not URL-encoded.
  my $c = control(ControlAddress => '203.0.113.9', User => 'admin', Pass => 'p%41ss');
  $c->open();
  check_ok('monitor Pass is not percent-decoded',
    (($c->{password} // '') eq 'p%41ss') ? 1 : 0);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
