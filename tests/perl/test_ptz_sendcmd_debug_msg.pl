#!/usr/bin/perl
#
# ZoneMinder::Control::DCS3415, ::mjpgStreamer and ::Toshiba_IK_WB11A sendCmd
# debug message.
#
# sendCmd called printMsg as a plain function, so printMsg's first argument
# became the command instead of the object and its message argument became the
# literal "Tx" sendCmd passes along. The debug line was "Tx[2]" instead of the
# command, and printMsg never saw the object it is written to use.
#
# Run as: perl tests/perl/test_ptz_sendcmd_debug_msg.pl
# Set ZM_CONTROL_LIB to a directory holding ZoneMinder/ to test another copy.
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_CONTROL_LIB} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

use LWP::UserAgent;
use HTTP::Request;
use ZoneMinder::Control::DCS3415;
use ZoneMinder::Control::mjpgStreamer;
use ZoneMinder::Control::Toshiba_IK_WB11A;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '(undef)')."\n  want: ".(defined $want ? $want : '(undef)')."\n";
  }
}

our @log;

package FakeResponse;
sub new { return bless {}, shift; }
sub is_success { return 1; }
sub status_line { return '200 OK'; }

package FakeUA;
sub new { return bless {}, shift; }
sub request { return FakeResponse->new; }

package main;

sub send_and_log {
  my $module = shift;
  @log = ();

  {
    no warnings 'redefine';
    no strict 'refs';
    *{"${module}::Debug"} = sub { push @log, map { defined $_ ? $_ : '' } @_ };
  }

  my $obj = bless {Monitor => {ControlAddress => 'x'}, ua => FakeUA->new}, $module;
  my $result = $obj->sendCmd('abc');

  return ($log[0], $result);
}

for my $module (qw(
  ZoneMinder::Control::DCS3415
  ZoneMinder::Control::mjpgStreamer
  ZoneMinder::Control::Toshiba_IK_WB11A
)) {
  (my $name = $module) =~ s/^ZoneMinder::Control:://;

  my ($msg, $result) = send_and_log($module);
  check("$name sendCmd logs the command", $msg, 'abc[3]');
  check("$name sendCmd returns true", $result ? 'true' : 'false', 'true');
}

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
