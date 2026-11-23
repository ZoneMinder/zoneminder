#!/usr/bin/perl
#
# ZoneMinder::Control::LoftekSentinel diagonal PTZ tests.
#
# Tests:
# 1. Diagonal moves with AutoStopTimeout must start both directions before
#    stopping either.
# 3. moveStop after a diagonal without AutoStopTimeout stops both axes.
#
# Run as: perl tests/perl/test_lofteksentinel_diagonal.pl
#
use strict;
use warnings;
use FindBin;
our $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Control::LoftekSentinel;

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
  }, 'ZoneMinder::Control::LoftekSentinel';
  return ($control, $ua);
}

# 1. moveConUpRight starts up and right before stopping either
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConUpRight();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveConUpRight with AutoStopTimeout starts both before stopping',
    $commands, '0,6,1,7');
}

# 2. moveConDownRight starts down and right before stopping either
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConDownRight();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveConDownRight with AutoStopTimeout starts both before stopping',
    $commands, '2,6,3,7');
}

# 3. moveConUpLeft starts up and left before stopping either
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConUpLeft();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveConUpLeft with AutoStopTimeout starts both before stopping',
    $commands, '0,4,1,5');
}

# 4. moveConDownLeft starts down and left before stopping either
{
  my ($control, $ua) = camera(AutoStopTimeout => 1);
  $control->moveConDownLeft();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveConDownLeft with AutoStopTimeout starts both before stopping',
    $commands, '2,4,3,5');
}

# 5. moveConUpRight without AutoStopTimeout only starts both directions
{
  my ($control, $ua) = camera(AutoStopTimeout => 0);
  $control->moveConUpRight();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveConUpRight without AutoStopTimeout only starts',
    $commands, '0,6');
}

# 6. moveStop after a diagonal without AutoStopTimeout stops both axes
{
  my ($control, $ua) = camera(AutoStopTimeout => 0);
  $control->moveConUpRight();
  @{$ua->{reqs}} = ();
  $control->moveStop();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveStop after moveConUpRight sends both stop codes', $commands, '1,7');
}
# 7. a later single-axis move replaces the diagonal stop codes
{
  my ($control, $ua) = camera(AutoStopTimeout => 0);
  $control->moveConUpRight();
  $control->moveConUp();
  @{$ua->{reqs}} = ();
  $control->moveStop();
  my $commands = join(',', map { (($_->uri.'') =~ /command=(\d+)/)[0] } @{$ua->{reqs}});
  check('moveStop after a single move sends one stop code', $commands, '1');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
