#!/usr/bin/perl
#
# ZoneMinder::Control::IPCAMIOS picture orientation.
#
# Left and right step the camera's rotation through 1-4 and wrap.  The phone
# reports 0 for auto (what presetHome sets), and moveConRight turned that into
# rotation=-1, which is outside the cycle.
#
# Run as: perl tests/perl/test_ipcamios_rotation.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::IPCAMIOS;

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
sub new { my ($class, $rotation) = @_; return bless {rotation => $rotation, reqs => []}, $class; }
sub agent {}
sub request {
  my ($self, $req) = @_;
  my $url = $req->uri->as_string;
  push @{$self->{reqs}}, $url;
  # parameters? is a list of name:value fields, rotation being the eleventh
  my @fields = map { "field$_:0" } 0 .. 9;
  return FakeResponse->new(join(',', @fields, 'rotation:'.$self->{rotation}, 'exposure:0'));
}

package main;

sub rotation_sent {
  my ($method, $current) = @_;
  my $ua = FakeUA->new($current);
  my $c = bless {ua => $ua, Monitor => {ControlAddress => '192.168.1.50:8080'}}, 'ZoneMinder::Control::IPCAMIOS';
  $c->$method({});
  my ($sent) = (grep { /rotation=/ } @{$ua->{reqs}})[-1] =~ /rotation=(-?\d+)/;
  return $sent;
}

my %right = (0 => 4, 1 => 4, 2 => 1, 3 => 2, 4 => 3);
my %left  = (0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 1);
foreach my $current (sort keys %right) {
  check("moveConRight from rotation $current", rotation_sent('moveConRight', $current), $right{$current});
  check("moveConLeft from rotation $current", rotation_sent('moveConLeft', $current), $left{$current});
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
