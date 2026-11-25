#!/usr/bin/perl
#
# ZoneMinder::Control::Vivotek_ePTZ must implement zoomStop().
#
# The Vivotek ePTZ Controls row uses continuous zoom, and the web UI sends
# zoomStop when a continuous zoom button is released. The module had no
# zoomStop, so zmcontrol logged an error for every release. moveStop is
# already a deliberate no-op for this camera; zoomStop is too, and must not
# send a request.
#
# Run as: perl tests/perl/test_vivotek_zoomstop_release.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

BEGIN {
  # Optional CPAN module URI::Encode
  if (!eval { require URI::Encode; 1 }) {
    # Not installed here: define a pass-through at run time, so the stub
    # cannot collide with the real module where it is installed.
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use ZoneMinder::Control::Vivotek_ePTZ;

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

package FakeUserAgent;
sub new { return bless {count => 0}, $_[0]; }
sub request { $_[0]->{count}++; die "unexpected request\n"; }

package main;

$ZoneMinder::Control::Vivotek_ePTZ::BASE_URL = 'http://192.0.2.1';

my $ua = FakeUserAgent->new();
my $control = bless {
  Monitor => {
    Id => 1,
    ControlAddress => '192.0.2.1:80',
  },
  state => 'open',
  ua => $ua,
}, 'ZoneMinder::Control::Vivotek_ePTZ';

check('zmcontrol can run zoomStop', $control->can('zoomStop') ? 'yes' : 'no', 'yes');
my $died = 0;
if ($control->can('zoomStop')) {
  eval { $control->zoomStop({}); 1 } or $died = 1;
}
check('zoomStop does not die', $died, 0);
check('zoomStop sends no request', $ua->{count}, 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
