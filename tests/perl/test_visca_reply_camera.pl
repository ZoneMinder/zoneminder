#!/usr/bin/perl
#
# ZoneMinder::Control::Visca checks that a reply comes from the camera the
# command was sent to. VISCA chains up to seven cameras on one serial line, so a
# reply from another camera must not be taken as this camera's ack. The check
# used "=" instead of "==", which made every reply match.
#
# Run as: perl tests/perl/test_visca_reply_camera.pl
# ZM_TEST_ROOT points it at another copy of the source.
#
use strict;
use warnings;
use FindBin;
our $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Control::Visca;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

package FakePort;
sub new { my ($class, @replies) = @_; return bless {replies => [@replies]}, $class; }
sub write { return length($_[1]); }
sub read {
  my $self = shift;
  my $r = shift @{$self->{replies}};
  return defined $r ? (length($r), $r) : (0, '');
}

package main;

sub ack_for {
  my (@replies) = @_;
  my $control = bless {
    Monitor => { Id => 1, ControlAddress => 1, AutoStopTimeout => 0 },
    state => 'open',
    port => FakePort->new(@replies),
  }, 'ZoneMinder::Control::Visca';
  # Address camera 1: header 0x81. Wait for the ack only.
  my $result = $control->sendCmd([0x81, 0x01, 0x06, 0x04, 0xff], 1, 0);
  return $result ? 'acked' : 'not acked';
}

check('an ack from camera 1 is accepted', ack_for(pack('C*', 0x90, 0x41, 0xff)), 'acked');
check('an ack from camera 2 is not taken as camera 1\'s', ack_for(pack('C*', 0xa0, 0x41, 0xff)), 'not acked');

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
