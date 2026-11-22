#!/usr/bin/perl
#
# ZoneMinder::Control::Keekoon stop command and ControlAddress credentials.
#
# The Controls row advertises continuous moves, so the web ui sends moveStop
# when a move button is released and when the centre button is clicked.  The
# module never implemented moveStop, so zmcontrol logged "does not implement
# 'moveStop'" and the camera kept moving.
#
# The module also parses the credentials out of ControlAddress with a regex
# that splits on the first colon inside the userinfo, so a password containing
# a colon was cut in half and the camera answered 401 to every move.
#
# Run as: perl tests/perl/test_keekoon_ptz.pl
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

use MIME::Base64 qw(encode_base64);
use ZoneMinder::Control::Keekoon;

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
sub new { my ($class, $address) = @_; return bless {ControlAddress => $address, AutoStopTimeout => 0}, $class; }

package main;

sub camera {
  my ($address) = @_;
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new($address), state => 'open',
  }, 'ZoneMinder::Control::Keekoon';
  return ($control, $ua);
}

sub basic {
  return 'Basic '.encode_base64($_[0], '');
}

# Send one move and report the credentials it carried.  A username the regex
# wrongly built with a colon in it makes HTTP::Request die, so keep that as the
# observed value instead of letting the test abort.
sub auth_for {
  my ($address) = @_;
  my ($control, $ua) = camera($address);
  my $error;
  eval { $control->moveConUp(); 1 } or $error = $@;
  return 'died: '.$error if $error;
  return $ua->{reqs}[0]->header('Authorization');
}

# moveStop, which the Controls row needs for a continuous move protocol.
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  check('moveStop is implemented', $control->can('moveStop') ? 1 : 0, 1);
  $control->moveStop();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveStop stops every direction the module can start',
    join(',', @cmds), '1,3,5,7');
}

# A move is unchanged: the direction's own command, and its stop after the
# configured AutoStopTimeout.
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->moveConRight();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConRight sends the right command', join(',', @cmds), '6');
}
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 1000;
  $control->moveConRight();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConRight still auto stops with the right stop code',
    join(',', @cmds), '6,7');
}

# Credentials are split on the first colon, not the last one.
check('a password containing a colon keeps the whole password',
  auth_for('http://admin:pa:ss@192.0.2.1:80'), basic('admin:pa:ss'));

# ...without breaking the ordinary case or a password containing '@'.
check('a plain password still works',
  auth_for('http://admin:adminpass@192.0.2.1:80'), basic('admin:adminpass'));
check('a password containing @ still works',
  auth_for('http://admin:p@ss@192.0.2.1:80'), basic('admin:p@ss'));

# The address is not swallowed into the credentials.
{
  my ($control, $ua) = camera('https://admin:pa:ss@192.0.2.1:8080');
  my $error;
  eval { $control->moveConUp(); 1 } or $error = $@;
  check('the host and port still come from the address',
    $error ? 'died' : $ua->{reqs}[0]->uri.'',
    'https://192.0.2.1:8080/decoder_control.cgi?command=0');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
