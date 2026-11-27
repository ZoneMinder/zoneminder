#!/usr/bin/perl
#
# ZoneMinder::Control::FI8918W PTZ and IR control tests.
#
# Tests stop commands for directional moves (previously hardcoded to command=1
# Stop Up for all directions, leaving down/left/right continuous moves unstopped),
# and wake/sleep IR commands (previously inverted: wake sent 95 IR off, sleep sent 94 IR on).
#
# Run as: perl tests/perl/test_fi8918w_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI8918W;

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
sub new { my ($class, $body) = @_; return bless {body => $body}, $class; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub decoded_content { $_[0]->{body} }

package FakeUA;
sub new { my ($class) = @_; return bless {reqs => []}, $class; }
sub agent {}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new('ok');
}

package FakeMonitor;
sub new {
  my ($class, %opts) = @_;
  return bless {
    Id => 1,
    ControlAddress => '192.0.2.1:80',
    ControlDevice => 'user=admin&pwd=secret',
    AutoStopTimeout => $opts{AutoStopTimeout} // 0,
  }, $class;
}

package main;

sub camera {
  my (%opts) = @_;
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(%opts),
  }, 'ZoneMinder::Control::FI8918W';
  return ($control, $ua);
}

# 1. autoStop: moveConDown must stop with command=3, not command=1
{
  my ($control, $ua) = camera(AutoStopTimeout => 100);
  $control->moveConDown();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConDown sends command=2', ($urls[0] // '') =~ /command=2\b/ ? 1 : 0, 1);
  check('moveConDown autoStop sends command=3 (Stop Down)',
    ($urls[1] // '') =~ /command=3\b/ ? 1 : 0, 1);
}

# 2. autoStop: moveConLeft must stop with command=7, not command=1
{
  my ($control, $ua) = camera(AutoStopTimeout => 100);
  $control->moveConLeft();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConLeft sends command=6', ($urls[0] // '') =~ /command=6\b/ ? 1 : 0, 1);
  check('moveConLeft autoStop sends command=7 (Stop Left)',
    ($urls[1] // '') =~ /command=7\b/ ? 1 : 0, 1);
}

# 3. autoStop: moveConRight must stop with command=5, not command=1
{
  my ($control, $ua) = camera(AutoStopTimeout => 100);
  $control->moveConRight();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConRight sends command=4', ($urls[0] // '') =~ /command=4\b/ ? 1 : 0, 1);
  check('moveConRight autoStop sends command=5 (Stop Right)',
    ($urls[1] // '') =~ /command=5\b/ ? 1 : 0, 1);
}

# 4. moveStop: moveConDown followed by moveStop must send command=3
{
  my ($control, $ua) = camera(AutoStopTimeout => 0);
  $control->moveConDown();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConDown moveStop sends command=3',
    ($urls[-1] // '') =~ /command=3\b/ ? 1 : 0, 1);
}

# 5. IR commands: wake (IR on) must send command=94, sleep (IR off) must send command=95
{
  my ($control, $ua) = camera();
  $control->wake();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('wake (IR on) sends command=94',
    ($urls[-1] // '') =~ /command=94\b/ ? 1 : 0, 1);
}

{
  my ($control, $ua) = camera();
  $control->sleep();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('sleep (IR off) sends command=95',
    ($urls[-1] // '') =~ /command=95\b/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
