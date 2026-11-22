#!/usr/bin/perl
#
# ZoneMinder::Control::Vivotek_ePTZ speed clamping and request shape tests.
#
# 1. A command without a speed (reset) produced "stream=0&&move=home".
# 2. speed values must be clamped to the camera's supported range of -5..5.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  # Optional CPAN module URI::Encode
  if (!eval { require URI::Encode; 1 }) {
    # Not installed here: define a pass-through at run time, so the stub
    # cannot collide with the real module where it is installed.
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use ZoneMinder::Control::Vivotek_ePTZ;

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
sub new { return bless {ok => $_[1]}, $_[0]; }
sub is_success { $_[0]->{ok} }
sub status_line { $_[0]->{ok} ? '200 OK' : '500 fail' }

package FakeUserAgent;
sub new { return bless {last_req => undef}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  $self->{last_req} = $req;
  return FakeResponse->new(1);
}

package main;

$ZoneMinder::Control::Vivotek_ePTZ::BASE_URL = 'http://192.0.2.1';

my $ua = FakeUserAgent->new();
my $control = bless {
  Monitor => {
    Id => 1,
    ControlAddress => '192.0.2.1:80',
  },
  state => 'open',
  ua => $ua,
}, 'ZoneMinder::Control::Vivotek_ePTZ';

sub run_cmd {
  my ($method, $params) = @_;
  $ua->{last_req} = undef;
  $control->$method($params);
  return $ua->{last_req} ? $ua->{last_req}->uri()->as_string() : undef;
}

# 1. command without a speed
check('reset sends move=home without an empty parameter',
  run_cmd('reset'),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&move=home');

# 2. Speed clamping tests
check('moveConLeft clamps speed below -5 to -5',
  run_cmd('moveConLeft', {panspeed => 0}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedpan=-5&move=left');

check('moveConLeft clamps speed above 5 to 5',
  run_cmd('moveConLeft', {panspeed => 12}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedpan=5&move=left');

foreach my $panspeed (1, 6, 11) {
  my ($left) = run_cmd('moveConLeft', {panspeed => $panspeed}) =~ /speedpan=(-?\d+)/;
  my ($right) = run_cmd('moveConRight', {panspeed => $panspeed}) =~ /speedpan=(-?\d+)/;
  check("left and right pan at the same speed for panspeed $panspeed", $left, $right);
}

check('moveConRight clamps speed below -5 to -5',
  run_cmd('moveConRight', {panspeed => 0}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedpan=-5&move=right');

check('moveConRight clamps speed above 5 to 5',
  run_cmd('moveConRight', {panspeed => 12}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedpan=5&move=right');

check('moveConUp clamps tiltspeed below -5 to -5',
  run_cmd('moveConUp', {tiltspeed => 0}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedtilt=-5&move=up');

check('moveConUp clamps tiltspeed above 5 to 5',
  run_cmd('moveConUp', {tiltspeed => 12}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedtilt=5&move=up');

check('zoomConTele clamps speed below -5 to -5',
  run_cmd('zoomConTele', {speed => 0}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedzoom=-5&zoom=tele');

check('zoomConTele clamps speed above 5 to 5',
  run_cmd('zoomConTele', {speed => 12}),
  'http://192.0.2.1/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&speedzoom=5&zoom=tele');

# open() takes the host and credentials from the ControlAddress.
package FakeOpenResponse;
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub content { '' }

package FakeOpenUA;
sub new { return bless {urls => []}, $_[0]; }
sub get {
  my ($self, $url) = @_;
  push @{$self->{urls}}, $url;
  return FakeOpenResponse->new();
}
sub credentials {}
sub agent {}

package main;

sub vivotek_open {
  my ($addr) = @_;
  my $open_ua = FakeOpenUA->new();
  no warnings 'redefine';
  local *LWP::UserAgent::new = sub { return $open_ua; };
  $ZoneMinder::Control::Vivotek_ePTZ::ADDRESS = '';
  $ZoneMinder::Control::Vivotek_ePTZ::USERNAME = 'admin';
  $ZoneMinder::Control::Vivotek_ePTZ::PASSWORD = '';
  my $c = bless {
    Monitor => { Id => 1, ControlAddress => $addr, ControlDevice => '', Path => '' },
  }, 'ZoneMinder::Control::Vivotek_ePTZ';
  $c->open();
  return @{$open_ua->{urls}} ? URI->new($open_ua->{urls}[0])->host_port() : '';
}

check('address without credentials connects to that host',
  vivotek_open('192.0.2.1'), '192.0.2.1:80');
check('address without credentials keeps the default username',
  $ZoneMinder::Control::Vivotek_ePTZ::USERNAME, 'admin');

check('address with a port and no credentials connects to it',
  vivotek_open('192.0.2.1:8080'), '192.0.2.1:8080');
check('address with an empty username still connects to the host',
  vivotek_open(':secret@192.0.2.1'), '192.0.2.1:80');

vivotek_open('root:p@ss@192.0.2.1:8080');
check('password containing @ is kept whole',
  $ZoneMinder::Control::Vivotek_ePTZ::PASSWORD, 'p@ss');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
