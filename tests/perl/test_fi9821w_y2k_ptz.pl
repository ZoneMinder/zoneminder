#!/usr/bin/perl
#
# ZoneMinder::Control::FI9821W_Y2k credential handling.
#
# ControlDevice holds "user:password" and was split on every ':', so a
# password containing one was cut at it and the camera rejected the login.
# FoscamHD, which this module inherits from, splits on the first ':' only.
#
# Run as: perl tests/perl/test_fi9821w_y2k_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI9821W_Y2k;

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
  push @{$self->{reqs}}, $req->uri->as_string;
  return FakeResponse->new;
}

package main;

# The credentials the camera is shown for a given ControlDevice.
sub credentials_sent {
  my ($device) = @_;
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua,
    Monitor => {Id => 1, AutoStopTimeout => 0, ControlAddress => '192.0.2.1', ControlDevice => $device},
  }, 'ZoneMinder::Control::FI9821W_Y2k';
  $control->sendCmd('ptzStopRun');
  my ($url) = @{$ua->{reqs}};
  my ($creds) = $url =~ /(usr=.*?)%26\d+$/;
  return $creds;
}

check('user and password', credentials_sent('bob:secret'), 'usr=bob&pwd=secret');
check('password alone defaults the user to admin', credentials_sent('secret'), 'usr=admin&pwd=secret');
check('password with a colon is kept whole', credentials_sent('bob:se:cret'), 'usr=bob&pwd=se:cret');
check('password ending in a colon is kept whole', credentials_sent('bob:secret:'), 'usr=bob&pwd=secret:');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
