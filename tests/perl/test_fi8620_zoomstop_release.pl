#!/usr/bin/perl
#
# ZoneMinder::Control::FI8620_Y2k must implement zoomStop() and focusStop().
#
# The FI8620 Controls row uses continuous zoom and focus, and the web UI sends
# zoomStop or focusStop when such a button is released. The module only had
# moveStop, so zmcontrol logged an error for every release instead of sending
# the camera's ptz stop.
#
# Run as: perl tests/perl/test_fi8620_zoomstop_release.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::FI8620_Y2k;

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

my $ua = FakeUA->new();
my $c = bless {
  Monitor => {ControlAddress => '10.0.0.5:80', ControlDevice => 'pw', AutoStopTimeout => 0},
  ua => $ua,
  state => 'open',
}, 'ZoneMinder::Control::FI8620_Y2k';

for my $cmd (qw(zoomStop focusStop)) {
  check("zmcontrol can run $cmd", $c->can($cmd) ? 'yes' : 'no', 'yes');
  next if !$c->can($cmd);
  @{$$ua{requests}} = ();
  $c->$cmd();
  my @urls = map { $_->uri->as_string } @{$$ua{requests}};
  check("$cmd sends the ptz stop", scalar(grep { m{ptzctrl\.cgi\?-step=0&-act=stop$} } @urls), 1);
  check("$cmd does not start a move", scalar(grep { m{-act=(zoomin|zoomout|focusin|focusout|up|down|left|right)} } @urls), 0);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
