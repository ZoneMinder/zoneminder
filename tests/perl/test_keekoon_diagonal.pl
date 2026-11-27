#!/usr/bin/perl
#
# ZoneMinder::Control::Keekoon diagonal moves with AutoStopTimeout.
#
# The diagonal buttons must start both directions before stopping either, so
# an AutoStopTimeout cannot cut the first direction short while the second is
# still being sent.
#
# Run as: perl tests/perl/test_keekoon_diagonal.pl
#
use strict;
use warnings;
use FindBin;

our $root;
BEGIN {
  $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
}
use lib "$root/scripts/ZoneMinder/lib";

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

# Every diagonal starts both directions before either is stopped.
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 1;
  $control->moveConUpRight();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConUpRight starts both directions before stopping either',
    join(',', @cmds), '0,6,1,7');
}
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 1;
  $control->moveConDownRight();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConDownRight starts both directions before stopping either',
    join(',', @cmds), '2,6,3,7');
}
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 1;
  $control->moveConUpLeft();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConUpLeft starts both directions before stopping either',
    join(',', @cmds), '0,4,1,5');
}
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 1;
  $control->moveConDownLeft();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConDownLeft starts both directions before stopping either',
    join(',', @cmds), '2,4,3,5');
}

# With no AutoStopTimeout nothing is stopped.
{
  my ($control, $ua) = camera('http://admin:adminpass@192.0.2.1:80');
  $control->{Monitor}->{AutoStopTimeout} = 0;
  $control->moveConUpRight();
  my @cmds = map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}};
  check('moveConUpRight sends no stop without an AutoStopTimeout',
    join(',', @cmds), '0,6');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
