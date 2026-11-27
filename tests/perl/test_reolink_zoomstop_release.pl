#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink must implement zoomStop().
#
# Releasing a continuous zoom button makes the web UI send zoomStop, not
# moveStop. Reolink.pm only had moveStop, so zmcontrol logged an error and the
# camera kept zooming unless an Auto Stop Timeout was set.
#
# Run as: perl tests/perl/test_reolink_zoomstop_release.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::Reolink;

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
sub new { return bless {}, shift; }
sub is_success { 1 }

package FakeUA;
sub new { return bless {requests => []}, shift; }
sub request { my ($self, $req) = @_; push @{$$self{requests}}, $req; return FakeResponse->new(); }

package main;

{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Reolink::loadMonitor = sub { };
}

my $c = bless {Monitor => {ControlAddress => 'admin:pw@10.0.0.5:8000', AutoStopTimeout => 0}},
  'ZoneMinder::Control::Reolink';
$c->open();
my $ua = FakeUA->new();
$$c{ua} = $ua;

check('zmcontrol can run zoomStop', $c->can('zoomStop') ? 'yes' : 'no', 'yes');
$c->zoomStop() if $c->can('zoomStop');

my $count = scalar @{$$ua{requests}};
check('zoomStop sends one request', $count, 1);
my $req = $$ua{requests}[0];
my $body = $req ? $req->content : '';
check('zoomStop stops zoom', ($body =~ m{<Zoom>true</Zoom>}) ? 'yes' : 'no', 'yes');
check('zoomStop leaves pan/tilt alone', ($body =~ m{<PanTilt>false</PanTilt>}) ? 'yes' : 'no', 'yes');
check('zoomStop uses the Stop action', ($body =~ m{<Stop xmlns=}) ? 'yes' : 'no', 'yes');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
