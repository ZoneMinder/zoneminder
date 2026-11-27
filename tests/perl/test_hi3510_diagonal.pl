#!/usr/bin/perl
#
# ZoneMinder::Control::HiSilicon_Hi3510_CGI diagonal PTZ tests.
#
# Tests:
# 1. Diagonal moves with AutoStopTimeout must start both directions before
#    stopping either.
#
# Run as: perl tests/perl/test_hi3510_diagonal.pl
#
use strict;
use warnings;
use FindBin;
our $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Control::HiSilicon_Hi3510_CGI;

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
    host => '192.0.2.1',
    port => 80,
    username => 'admin',
    password => 'secret',
  }, 'ZoneMinder::Control::HiSilicon_Hi3510_CGI';
  return ($control, $ua);
}

sub actions {
  my ($ua) = @_;
  return join(',', map { (($_->uri.'') =~ /-act=(\w+)/)[0] } @{$ua->{reqs}});
}

# 1. moveConUpRight starts up and right before the single stop
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConUpRight();
  check('moveConUpRight with AutoStopTimeout starts both before stopping',
    actions($ua), 'up,right,stop');
}

# 2. moveConDownLeft starts down and left before the single stop
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConDownLeft();
  check('moveConDownLeft with AutoStopTimeout starts both before stopping',
    actions($ua), 'down,left,stop');
}

# 3. moveConUpRight without AutoStopTimeout only starts both directions
{
  my ($control, $ua) = camera(AutoStopTimeout => 0);
  $control->moveConUpRight();
  check('moveConUpRight without AutoStopTimeout only starts',
    actions($ua), 'up,right');
}

# 4. moveConUp alone starts up then stops
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConUp();
  check('moveConUp with AutoStopTimeout starts then stops',
    actions($ua), 'up,stop');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
