#!/usr/bin/perl
#
# The Foscam Y2k modules must pan left at the same speed as right for the same
# slider position. moveConLeft inverted the speed on FI8608W_Y2k and FI8620_Y2k,
# and skipped the inversion every other direction applies on FI9821W_Y2k and
# FI9831W, so a full speed click moved left at the slowest speed.
#
# Run as: sudo -u www-data perl tests/perl/test_foscam_y2k_left_speed.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI8608W_Y2k;
use ZoneMinder::Control::FI8620_Y2k;
use ZoneMinder::Control::FI9821W_Y2k;
use ZoneMinder::Control::FI9831W;

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

# What a move sends: the speed in the command, and the autostop delay.
sub run {
  my ($class, $method, $panspeed) = @_;
  my (@sent, @stops);
  no strict 'refs';
  no warnings 'redefine';
  local *{$class.'::sendCmd'} = sub { push @sent, $_[1]; return 1; };
  local *{$class.'::autoStop'} = sub { push @stops, $_[1]; };
  my $control = bless {Monitor => {Id => 1, AutoStopTimeout => 500000}}, $class;
  $control->$method({panspeed => $panspeed});
  my ($speed) = map { /speed(?:=|%3D)(\d+)/ ? $1 : () } @sent;
  return join(' ', 'speed='.(defined $speed ? $speed : '-'), 'autostop='.(defined $stops[0] ? $stops[0] : '-'));
}

foreach my $case (
  ['FI8608W_Y2k', 128], ['FI8608W_Y2k', 10],
  ['FI8620_Y2k', 63], ['FI8620_Y2k', 6],
  ['FI9821W_Y2k', 4], ['FI9821W_Y2k', 0],
  ['FI9831W', 4], ['FI9831W', 0],
) {
  my ($module, $panspeed) = @$case;
  my $class = 'ZoneMinder::Control::'.$module;
  check("$module moveConLeft matches moveConRight at panspeed $panspeed",
    run($class, 'moveConLeft', $panspeed), run($class, 'moveConRight', $panspeed));
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
