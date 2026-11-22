#!/usr/bin/perl
#
# ZoneMinder::Control::Wanscam brightness and contrast steps.
#
# The WanscamPT Controls row selects absolute iris and white with Min and Max
# step left at 0, so the web ui sends --step=0 for those buttons.  The module
# added step*16 to the brightness and step to the contrast, so every press
# read the current value and wrote the same value back: the buttons did
# nothing.  A step of 0 is now taken as one notch.
#
# Run as: perl tests/perl/test_wanscam_ptz.pl
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

use ZoneMinder::Control::Wanscam;

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
sub decoded_content { "var brightness=96;\nvar contrast=3;\n" }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new;
}

package FakeMonitor;
sub new { return bless {ControlAddress => '192.0.2.1:80', ControlDevice => 'user=admin&pwd=x', AutoStopTimeout => 0}, $_[0]; }

package main;

sub last_url {
  my ($method, $params) = @_;
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new, state => 'open',
  }, 'ZoneMinder::Control::Wanscam';
  $control->$method($params);
  return @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri.'' : '';
}

# What the ui sends for this Controls row.
check('irisAbsOpen with step 0 raises brightness', last_url('irisAbsOpen', {step => 0}),
  'http://192.0.2.1:80/camera_control.cgi?param=1&value=112&user=admin&pwd=x');
check('irisAbsClose with step 0 lowers brightness', last_url('irisAbsClose', {step => 0}),
  'http://192.0.2.1:80/camera_control.cgi?param=1&value=80&user=admin&pwd=x');
check('whiteAbsIn with step 0 raises contrast', last_url('whiteAbsIn', {step => 0}),
  'http://192.0.2.1:80/camera_control.cgi?param=2&value=4&user=admin&pwd=x');
check('whiteAbsOut with step 0 lowers contrast', last_url('whiteAbsOut', {step => 0}),
  'http://192.0.2.1:80/camera_control.cgi?param=2&value=2&user=admin&pwd=x');

# A configured step is still honoured.
check('irisAbsOpen with step 2', last_url('irisAbsOpen', {step => 2}),
  'http://192.0.2.1:80/camera_control.cgi?param=1&value=128&user=admin&pwd=x');
check('whiteAbsOut with step 2', last_url('whiteAbsOut', {step => 2}),
  'http://192.0.2.1:80/camera_control.cgi?param=2&value=1&user=admin&pwd=x');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
